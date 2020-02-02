<?php

namespace PN\ContentBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use PN\ContentBundle\Twig\VarsRuntime;

/**
 * @author Peter Nassef <peter.nassef@gmail.com>
 * @version 1.0
 */
class VarsExtension extends AbstractExtension {

    public function getFilters() {
        return array(
        );
    }

    public function getFunctions() {
        return array(
            new TwigFunction('getDCA', array(VarsRuntime::class, 'getDynamicContentAttribute'), ['is_safe' => ['html']]),
            new TwigFunction('editDCA', array(VarsRuntime::class, 'editDynamicContentAttribute'), ['is_safe' => ['html']]),
            new TwigFunction('openGalleryBtn', array(VarsRuntime::class, 'openGalleryBtn'), ['is_safe' => ['html']]),
        );
    }

    public function getName() {
        return 'pn.content.twig.extension';
    }

}
