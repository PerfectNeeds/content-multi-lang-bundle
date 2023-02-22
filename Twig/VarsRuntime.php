<?php

namespace PN\ContentBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PN\ContentBundle\Entity\DynamicContentAttribute;
use \Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsRuntime implements RuntimeExtensionInterface
{

    private $em;
    private $assetsManager;
    private $router;
    private $authorizationChecker;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Packages $assetsManager,
        EntityManagerInterface $em,
        RouterInterface $router,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->em = $em;
        $this->assetsManager = $assetsManager;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * get DynamicContentAttribute by ID
     *
     * @param type $dynamicContentAttributeId
     * @return string
     * @example {{ getDCA(11) }}
     *
     */
    public function getDynamicContentAttribute($dynamicContentAttributeId, $showEditBtn = true)
    {
        $dynamicContentAttribute = $this->em->getRepository(DynamicContentAttribute::class)->find($dynamicContentAttributeId);
        if (!$dynamicContentAttribute) {
            return "";
        }
        if ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_IMAGE and $dynamicContentAttribute->getImage() != null) {
            return $this->assetsManager->getUrl($dynamicContentAttribute->getImage()->getAssetPath());
        } elseif ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_DOCUMENT and $dynamicContentAttribute->getDocument() != null) {
            $params = ["document" => $dynamicContentAttribute->getDocument()->getId()];

            return $this->router->generate("download")."?d=".str_replace('"', "'",
                    json_encode($params));
        } elseif ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_HTML) {
            return $dynamicContentAttribute->getValue();
        }

        $editBtn = "";
        if ($showEditBtn == true and in_array($dynamicContentAttribute->getType(),
                [DynamicContentAttribute::TYPE_TEXT, DynamicContentAttribute::TYPE_LONGTEXT])) {
            $editBtn = $this->showEditBtn($dynamicContentAttribute->getId());
        }

        $return = "";
        if ($dynamicContentAttribute->getValue()) {
            $return = nl2br($dynamicContentAttribute->getValue());
        }

        return $return.$editBtn;
    }

    /**
     * edit DynamicContentAttribute by ID
     *
     * @param type $dynamicContentAttributeId
     * @return string
     * @example {{ editDCA(11) }}
     *
     */
    public function editDynamicContentAttribute($dynamicContentAttributeId)
    {
        return $this->showEditBtn($dynamicContentAttributeId);
    }

    private function isGranted($attributes)
    {
        if ($this->tokenStorage->getToken() == null) {
            return false;
        }

        return $this->authorizationChecker->isGranted($attributes);
    }

    private function showEditBtn($dynamicContentAttributeId)
    {
        if ($this->isGranted("ROLE_ADMIN") == false) {
            return '';
        }

        $url = $this->router->generate("dynamic_content_attribute_edit",
            ['id' => $dynamicContentAttributeId]);

        return ' <a href="'.$url.'" target="popup" onclick="window.open(\''.$url.'\',\'popup\',\'width=600,height=600\'); return false;" title="Edit">Edit</a>';
    }

    public function openGalleryBtn($postId)
    {
        $url = $this->router->generate("post_images_popup", ['id' => $postId]);

        return '<a href="'.$url.'" target="popup" onclick="window.open(\''.$url.'\',\'popup\',\'width=600,height=600\'); return false;" class="btn btn-default">Open Gallery</a>';
    }

}
