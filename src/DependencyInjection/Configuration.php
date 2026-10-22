<?php

namespace Wexample\SymfonySeo\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Wexample\PhpSchemaOrg\Class\SchemaOrgVocabulary;

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
                        // An address with a query is a variant of a page whose
                        // canonical is the address without it: a table sorted,
                        // filtered, paged. Crawled, they multiply without end —
                        // every column, every filter, every language. `Disallow:
                        // /*?` keeps the crawlers on the pages themselves.
                        ->booleanNode('disallow_query')
                            ->defaultTrue()
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
                // What the engines are told of the site in schema.org's words, on
                // every page: who it is about (`Person`, `Organization`), the site
                // itself (`WebSite`). Each object a JSON-LD block, `@context` added
                // where it is left out — checked against schema.org's vocabulary:
                // a misspelt type or property stops the build.
                ->arrayNode('structured_data')
                    ->normalizeKeys(false)
                    ->variablePrototype()->end()
                    ->validate()
                        ->always(static function (array $objects): array {
                            $violations = (new SchemaOrgVocabulary())->getViolations(array_values($objects));

                            if ([] !== $violations) {
                                throw new InvalidConfigurationException('wexample_symfony_seo.structured_data: '.implode(' ', $violations));
                            }

                            return $objects;
                        })
                    ->end()
                ->end()
                // What a page is, by its route: `about_index: { '@type': ProfilePage,
                // mainEntity: { '@id': '/#person' } }`. Completed with the page's
                // address (`#page`), its title and its language; checked as the
                // objects above are.
                ->arrayNode('structured_data_pages')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('route')
                    ->variablePrototype()->end()
                    ->validate()
                        ->always(static function (array $pages): array {
                            $vocabulary = new SchemaOrgVocabulary();
                            $violations = [];

                            foreach ($pages as $route => $page) {
                                foreach ($vocabulary->getViolations((array) $page, $route) as $violation) {
                                    $violations[] = $violation;
                                }
                            }

                            if ([] !== $violations) {
                                throw new InvalidConfigurationException('wexample_symfony_seo.structured_data_pages: '.implode(' ', $violations));
                            }

                            return $pages;
                        })
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
