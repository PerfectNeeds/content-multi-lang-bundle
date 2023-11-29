<?php

namespace PN\ContentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PN\ContentBundle\Entity\DynamicContentAttribute;
use PN\ServiceBundle\Service\ContainerParameterService;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DynamicContentService
{
    private EntityManagerInterface $em;
    private Packages $assetsManager;
    private RouterInterface $router;
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private Request $request;
    private ContainerParameterService $containerParameterService;

    public function __construct(
        EntityManagerInterface        $em,
        RouterInterface               $router,
        Packages                      $assetsManager,
        TokenStorageInterface         $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack                  $requestStack,
        ContainerParameterService     $containerParameterService
    )
    {
        $this->em = $em;
        $this->assetsManager = $assetsManager;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->request = $requestStack->getCurrentRequest();
        $this->containerParameterService = $containerParameterService;
    }

    public function getDynamicContentAttribute($dynamicContentAttributeId, $showEditBtn = true)
    {
        $dynamicContentValue = $this->getDynamicContentValueFromCache($dynamicContentAttributeId);

        $editBtn = "";
        if ($showEditBtn == true and in_array($dynamicContentValue["type"],
                [DynamicContentAttribute::TYPE_TEXT, DynamicContentAttribute::TYPE_LONGTEXT])) {
            $editBtn = $this->showEditBtn($dynamicContentAttributeId);
        }

        return $dynamicContentValue["value"] . $editBtn;
    }

    public function showEditBtn($dynamicContentAttributeId)
    {
        if ($this->isGranted("ROLE_ADMIN") == false) {
            return '';
        }

        $url = $this->router->generate("dynamic_content_attribute_edit", ['id' => $dynamicContentAttributeId]);

        return ' <a href="' . $url . '" target="popup" onclick="window.open(\'' . $url . '\',\'popup\',\'width=600,height=600\'); return false;" title="Edit">Edit</a>';
    }

    private function isGranted($attributes)
    {
        if ($this->tokenStorage->getToken() == null) {
            return false;
        }

        return $this->authorizationChecker->isGranted($attributes, null);
    }

    private function getDynamicContentValueFromCache($dynamicContentAttributeId)
    {
        $cache = $this->getFilesystemCache();
        $cacheKey = $this->getCacheName($dynamicContentAttributeId);
        $locale = $this->request instanceof Request ? $this->request->getLocale() : "none";

        if (!$cache->has($cacheKey)) {
            $dynamicContentAttributeValue = $this->getDynamicContentValue($dynamicContentAttributeId);

            $data = [
                "type" => $dynamicContentAttributeValue["type"],
                $locale => $dynamicContentAttributeValue["value"],
            ];

            $cache->set($cacheKey, $data, 2592000);// expire after 30 days
        }
        $data = $cache->get($cacheKey);
        if (!is_array($data)) {
            $data = [];
        }
        if (!array_key_exists($locale, $data)) {
//            $data = $cache->get($cacheKey);
            $data[$locale] = $dynamicContentAttributeValue = $this->getDynamicContentValue($dynamicContentAttributeId)["value"];
            $cache->set($cacheKey, $data, 2592000);// expire after 30 days
        }
        $data = $cache->get($cacheKey);

        return [
            "type" => $data["type"],
            "value" => $data[$locale],
        ];
    }

    public function removeDynamicContentValueFromCache($dynamicContentAttributeId)
    {
        $cacheKey = $this->getCacheName($dynamicContentAttributeId);
        $cache = $this->getFilesystemCache();
        $cache->delete($cacheKey);
    }

    private function getDynamicContentValue($dynamicContentAttributeId)
    {
        $dynamicContentAttribute = $this->em->getRepository('PNContentBundle:DynamicContentAttribute')->find($dynamicContentAttributeId);
        if (!$dynamicContentAttribute) {
            return ["type" => null, "value" => ""];
        }
        if ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_IMAGE and $dynamicContentAttribute->getImage() != null) {
            return [
                "type" => $dynamicContentAttribute->getType(),
                "value" => $this->assetsManager->getUrl($dynamicContentAttribute->getImage()->getAssetPath()),
            ];
        } elseif ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_DOCUMENT and $dynamicContentAttribute->getDocument() != null) {
            $params = ["document" => $dynamicContentAttribute->getDocument()->getId()];


            return [
                "type" => $dynamicContentAttribute->getType(),
                "value" => $this->router->generate("download") . "?d=" . str_replace('"', "'",
                        json_encode($params)),
            ];
        } elseif ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_HTML) {
            return [
                "type" => $dynamicContentAttribute->getType(),
                "value" => $dynamicContentAttribute->getValue(),
            ];
        }

        return [
            "type" => $dynamicContentAttribute->getType(),
            "value" => nl2br($dynamicContentAttribute->getValue()),
        ];
    }

    private function getFilesystemCache()
    {
        return new FilesystemCache('', 0, $this->containerParameterService->get('kernel.cache_dir') . '/data-cache');
    }

    private function getCacheName($id)
    {
        return 'dynamic_content_' . $id;
    }
}