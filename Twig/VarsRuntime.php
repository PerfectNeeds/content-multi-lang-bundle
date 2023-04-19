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
     * @param int $dynamicContentAttributeId
     * @return string
     * @example {{ getDCA(11) }}
     *
     */
    public function getDynamicContentAttribute(int $dynamicContentAttributeId, $showEditBtn = true): string
    {
        return $this->dynamicContentService->getDynamicContentAttribute($dynamicContentAttributeId, $showEditBtn);
    }

    /**
     * edit DynamicContentAttribute by ID
     *
     * @param int $dynamicContentAttributeId
     * @return string
     * @example {{ editDCA(11) }}
     *
     */
    public function editDynamicContentAttribute(int $dynamicContentAttributeId): string
    {
        return $this->dynamicContentService->showEditBtn($dynamicContentAttributeId);
    }

    public function openGalleryBtn($postId): string
    {
        $url = $this->router->generate("post_images_popup", ['id' => $postId]);

        return '<a href="' . $url . '" target="popup" onclick="window.open(\'' . $url . '\',\'popup\',\'width=600,height=600\'); return false;" class="btn btn-default">Open Gallery</a>';
    }

}
