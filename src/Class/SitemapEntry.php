<?php

namespace Wexample\SymfonySeo\Class;

/**
 * One page of the sitemap, with the same page in the other languages: the
 * engines list each for its readers rather than as copies of one another.
 */
readonly class SitemapEntry
{
    /**
     * @param array<string, string> $alternates url by language tag, `x-default`
     *                                          included; empty for a page in one language
     */
    public function __construct(
        public string $url,
        public array $alternates = [],
    ) {
    }
}
