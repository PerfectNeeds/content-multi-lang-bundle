<?php

namespace PN\ContentBundle\Twig;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PN\ContentBundle\Entity\DynamicContentAttribute;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsRuntime implements RuntimeExtensionInterface {

    private $container;
    private $em;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * get DynamicContentAttribute by ID
     *
     * @example {{ getDCA(11) }}
     *
     * @param type $dynamicContentAttributeId
     * @return string
     */
    public function getDynamicContentAttribute($dynamicContentAttributeId) {
        $dynamicContentAttribute = $this->em->getRepository('ContentBundle:DynamicContentAttribute')->find($dynamicContentAttributeId);
        if (!$dynamicContentAttribute) {
            return "";
        }
        if ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_IMAGE and $dynamicContentAttribute->getImage() != null) {
            $manager = $this->container->get('assets.packages');
            return $manager->getUrl($dynamicContentAttribute->getImage()->getAssetPath());
        }
        return $dynamicContentAttribute->getValue();
    }

}
