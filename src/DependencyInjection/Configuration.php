<?php

namespace Wexample\SymfonySeo\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_seo');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('robots')
                    ->addDefaultsIfNotSet()
                    ->children()
                        // Off, the route answers 404 and a static file can take over.
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        // What a staging environment wants: nothing crawled, whatever
                        // the bundles declare, without a file deployed per environment.
                        ->booleanNode('disallow_all')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('sitemaps')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('extra')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('favicon')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        // Null serves the neutral icon shipped with the bundle.
                        ->scalarNode('path')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
