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
                // The card a link to the app shows once shared — a chat, a mail,
                // a social network: the page's title and description, and a picture.
                ->arrayNode('open_graph')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        // A path under the public directory (`/images/og.png`) or
                        // an address; null, the card goes without a picture.
                        ->scalarNode('image')
                            ->defaultNull()
                        ->end()
                        // The name the card shows above the title; null, none.
                        ->scalarNode('site_name')
                            ->defaultNull()
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
