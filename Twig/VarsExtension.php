<?php

namespace PN\ContentBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsExtension extends AbstractExtension
{

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getDCA', [VarsRuntime::class, 'getDynamicContentAttribute'], ['is_safe' => ['html']]),
            new TwigFunction('editDCA', [VarsRuntime::class, 'editDynamicContentAttribute'], ['is_safe' => ['html']]),
            new TwigFunction('openGalleryBtn', [VarsRuntime::class, 'openGalleryBtn'], ['is_safe' => ['html']]),
        ];
    }

    public function getName(): string
    {
        return 'pn.content.twig.extension';
    }

}
