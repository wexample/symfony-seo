<?php

namespace Wexample\SymfonySeo\Interface;

use Wexample\SymfonySeo\Class\SitemapRoute;

/**
 * Implemented by any service holding pages whose path takes a parameter: the
 * articles of a blog, the projects of a portfolio. Being autoconfigured is
 * enough for them to land in the sitemap, in every language their route
 * offers and under the same exclusions as the other pages.
 */
interface SitemapProviderInterface
{
    /**
     * @return iterable<SitemapRoute>
     */
    public function getSitemapRoutes(): iterable;
}
