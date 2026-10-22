<?php

namespace Wexample\SymfonySeo\Class;

/**
 * A page the routes cannot list alone: its path asks for a parameter — a
 * slug, an id — that only the app knows the values of. The language is left
 * out: the sitemap fills in each one the route offers.
 */
readonly class SitemapRoute
{
    /**
     * @param array<string, string|int> $parameters
     */
    public function __construct(
        public string $name,
        public array $parameters = [],
    ) {
    }
}
