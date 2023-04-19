<?php

namespace PN\ContentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PN\ContentBundle\Entity\DynamicContentAttribute;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DynamicContentService
{
    private EntityManagerInterface $em;
    private Packages $assetsManager;
    private RouterInterface $router;
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private ?Request $request = null;

    public function __construct(
        EntityManagerInterface        $em,
        RouterInterface               $router,
        Packages                      $assetsManager,
        TokenStorageInterface         $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack                  $requestStack
    )
    {
        $this->em = $em;
        $this->assetsManager = $assetsManager;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        if ($requestStack instanceof RequestStack) {
            $this->request = $requestStack->getCurrentRequest();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getDynamicContentAttribute($dynamicContentAttributeId, bool $showEditBtn = true): string
    {
        $dynamicContentValue = $this->getDynamicContentValueFromCache($dynamicContentAttributeId);

        $editBtn = "";
        if ($showEditBtn === true and in_array($dynamicContentValue["type"],
                [DynamicContentAttribute::TYPE_TEXT, DynamicContentAttribute::TYPE_LONGTEXT])) {
            $editBtn = $this->showEditBtn($dynamicContentAttributeId);
        }

        return $dynamicContentValue["value"] . $editBtn;
    }

    public function showEditBtn($dynamicContentAttributeId): string
    {
        if (!$this->isGranted("ROLE_ADMIN")) {
            return '';
        }

        $url = $this->router->generate("dynamic_content_attribute_edit", ['id' => $dynamicContentAttributeId]);

        return ' <a href="' . $url . '" target="popup" onclick="window.open(\'' . $url . '\',\'popup\',\'width=600,height=600\'); return false;" title="Edit">Edit</a>';
    }

    private function isGranted(string $attributes): bool
    {
        if ($this->tokenStorage->getToken() == null) {
            return false;
        }

        return $this->authorizationChecker->isGranted($attributes, null);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getDynamicContentValueFromCache($dynamicContentAttributeId): array
    {
        $cache = new FilesystemAdapter();

        $cacheKey = $this->getCacheName($dynamicContentAttributeId);
        $locale = $this->request instanceof Request ? $this->request->getLocale() : "none";


        $data = $cache->get($cacheKey, function (ItemInterface $item) use ($dynamicContentAttributeId, $locale) {
            $item->expiresAfter(2592000); // expire after 30 days
            $dynamicContentAttributeValue = $this->getDynamicContentValue($dynamicContentAttributeId);

            return [
                "type" => $dynamicContentAttributeValue["type"],
                $locale => $dynamicContentAttributeValue["value"],
            ];

        });

        if (!array_key_exists($locale, $data)) {
            $cache->delete($cacheKey);
            $data = $cache->get($cacheKey, function (ItemInterface $item) use ($dynamicContentAttributeId, $locale, $data) {
                $item->expiresAfter(2592000); // expire after 30 days

                $data[$locale] = $this->getDynamicContentValue($dynamicContentAttributeId)["value"];
                return $data;
            });
        }

        return [
            "type" => $data["type"],
            "value" => $data[$locale],
        ];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function removeDynamicContentValueFromCache($dynamicContentAttributeId): void
    {
        $cacheKey = $this->getCacheName($dynamicContentAttributeId);

        $cache = new FilesystemAdapter();
        $cache->delete($cacheKey);
    }

    private function getDynamicContentValue($dynamicContentAttributeId): array
    {
        $dynamicContentAttribute = $this->em->getRepository(DynamicContentAttribute::class)->find($dynamicContentAttributeId);
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

    private function getCacheName($id): string
    {
        return 'dynamic_content_' . $id;
    }
}