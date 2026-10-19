<?php

namespace Wexample\SymfonySeo\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonySeo\Service\SitemapService;

#[AsController]
class SitemapController
{
    final public const string ROUTE_SITEMAP = 'seo_sitemap';

    private const string NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    private const string NAMESPACE_XHTML = 'http://www.w3.org/1999/xhtml';

    /**
     * Like robots.txt, a path the standard sets: no underscore.
     */
    #[Route(
        path: '/sitemap.xml',
        name: self::ROUTE_SITEMAP,
        methods: ['GET', 'HEAD']
    )]
    public function sitemap(SitemapService $sitemapService): Response
    {
        if (! $sitemapService->isEnabled()) {
            throw new NotFoundHttpException('The sitemap of this app is not served by symfony-seo.');
        }

        // Namespaces declared once, on the root: thousands of links carry only
        // their prefix.
        $document = new \DOMDocument('1.0', 'UTF-8');
        $urlset = $document->createElement('urlset');
        $urlset->setAttribute('xmlns', self::NAMESPACE);
        $urlset->setAttribute('xmlns:xhtml', self::NAMESPACE_XHTML);
        $document->appendChild($urlset);

        foreach ($sitemapService->getEntries() as $entry) {
            $url = $document->createElement('url');
            $loc = $document->createElement('loc');
            $loc->appendChild($document->createTextNode($entry->url));
            $url->appendChild($loc);

            foreach ($entry->alternates as $language => $href) {
                $link = $document->createElement('xhtml:link');
                $link->setAttribute('rel', 'alternate');
                $link->setAttribute('hreflang', $language);
                $link->setAttribute('href', $href);
                $url->appendChild($link);
            }

            $urlset->appendChild($url);
        }

        return new Response(
            $document->saveXML(),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}
