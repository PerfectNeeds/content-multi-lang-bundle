<?php

namespace PN\ContentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface {

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pn_content');

        $rootNode
                ->children()
                ->scalarNode('post_class')
                ->isRequired()
                ->cannotBeEmpty()
                ->end()
                ->scalarNode('post_translation_class')
                ->isRequired()
                ->cannotBeEmpty()
                ->end()
        ;

        return $treeBuilder;
    }

}
