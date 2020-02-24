<?php

namespace PN\ContentBundle\Twig;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PN\ContentBundle\Entity\DynamicContentAttribute;
use \Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsRuntime implements RuntimeExtensionInterface {

    private $container;
    private $em;
    private $assetsManager;

    public function __construct(ContainerInterface $container, TokenStorageInterface $tokenStorage, Packages $assetsManager) {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
        $this->assetsManager = $assetsManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * get DynamicContentAttribute by ID
     *
     * @example {{ getDCA(11) }}
     *
     * @param type $dynamicContentAttributeId
     * @return string
     */
    public function getDynamicContentAttribute($dynamicContentAttributeId, $showEditBtn = true) {
        $dynamicContentAttribute = $this->em->getRepository('PNContentBundle:DynamicContentAttribute')->find($dynamicContentAttributeId);
        if (!$dynamicContentAttribute) {
            return "";
        }
        if ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_IMAGE and $dynamicContentAttribute->getImage() != null) {
            return $this->assetsManager->getUrl($dynamicContentAttribute->getImage()->getAssetPath());
        } elseif ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_DOCUMENT and $dynamicContentAttribute->getDocument() != null) {
            $params = ["document" => $dynamicContentAttribute->getDocument()->getId()];
            return $this->container->get("router")->generate("download") . "?d=" . str_replace('"', "'", json_encode($params));
        } elseif ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_HTML) {
            return $dynamicContentAttribute->getValue();
        }

        $editBtn = "";
        if ($showEditBtn == true and in_array($dynamicContentAttribute->getType(), [DynamicContentAttribute::TYPE_TEXT, DynamicContentAttribute::TYPE_LONGTEXT])) {
            $editBtn = $this->showEditBtn($dynamicContentAttribute->getId());
        }

        return nl2br($dynamicContentAttribute->getValue()) . $editBtn;
    }

    /**
     * edit DynamicContentAttribute by ID
     *
     * @example {{ editDCA(11) }}
     *
     * @param type $dynamicContentAttributeId
     * @return string
     */
    public function editDynamicContentAttribute($dynamicContentAttributeId) {
        return $this->showEditBtn($dynamicContentAttributeId);
    }

    private function isGranted($attributes) {
        if (!$this->container->has('security.authorization_checker')) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }
        if ($this->tokenStorage->getToken() == null) {
            return false;
        }

        return $this->container->get('security.authorization_checker')->isGranted($attributes, null);
    }

    private function showEditBtn($dynamicContentAttributeId) {
        if ($this->isGranted("ROLE_ADMIN") == false) {
            return '';
        }

        $url = $this->container->get("router")->generate("dynamic_content_attribute_edit", ['id' => $dynamicContentAttributeId]);

        return ' <a href="' . $url . '" target="popup" onclick="window.open(\'' . $url . '\',\'popup\',\'width=600,height=600\'); return false;" title="Edit"><i class="fa fa-pencil"></i></a>';
    }

    public function openGalleryBtn($postId) {
        $url = $this->container->get("router")->generate("post_images_popup", ['id' => $postId]);
        return '<a href="' . $url . '" target="popup" onclick="window.open(\'' . $url . '\',\'popup\',\'width=600,height=600\'); return false;" class="btn btn-default">Open Gallery</a>';
    }

}
