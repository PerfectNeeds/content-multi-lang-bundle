<?php

namespace PN\ContentBundle\Twig;

use PN\ContentBundle\Service\DynamicContentService;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsRuntime implements RuntimeExtensionInterface
{

    private RouterInterface $router;
    private DynamicContentService $dynamicContentService;

    public function __construct(RouterInterface $router, DynamicContentService $dynamicContentService)
    {
        $this->router = $router;
        $this->dynamicContentService = $dynamicContentService;
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
        return $this->dynamicContentService->getDynamicContentAttribute($dynamicContentAttributeId, $showEditBtn);
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
        return $this->dynamicContentService->showEditBtn($dynamicContentAttributeId, $showEditBtn);
    }

    public function openGalleryBtn($postId)
    {
        $url = $this->router->generate("post_images_popup", ['id' => $postId]);

        return '<a href="'.$url.'" target="popup" onclick="window.open(\''.$url.'\',\'popup\',\'width=600,height=600\'); return false;" class="btn btn-default">Open Gallery</a>';
    }

}
