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
                // Every page a visitor can open, in every language, for the engines
                // to find without following links: /sitemap.xml, named in robots.txt.
                ->arrayNode('sitemap')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        // Paths left out on top of what robots.txt disallows, as
                        // regular expressions (`^/[^/]+/login`, the language prefix
                        // included): pages reachable but not worth a search result.
                        ->arrayNode('exclude')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                // Hosts the app answers on only to send visitors elsewhere — a
                // former domain pointed at the same server: `docs.example.com:
                // { to: 'https://example.com' }`. Done before routing, so no route
                // of the app can answer for them.
                ->arrayNode('redirect_hosts')
                    // A host is a key as written: `old-site.com` keeps its dash.
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('host')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('to')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            // Off, every page lands on `to` itself: the old paths
                            // rarely mean anything on the new site.
                            ->booleanNode('keep_path')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('permanent')
                                ->defaultTrue()
                            ->end()
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
