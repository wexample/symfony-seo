<?php

namespace Wexample\SymfonySeo\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonySeo\Class\SitemapEntry;
use Wexample\SymfonySeo\Service\SitemapService;

#[AsController]
class SitemapController
{
    final public const string ROUTE_SITEMAP = 'seo_sitemap';

    final public const string ROUTE_SITEMAP_FILE = 'seo_sitemap_file';

    private const string NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private const string NAMESPACE_XHTML = 'http://www.w3.org/1999/xhtml';

    public function __construct(
        private readonly SitemapService $sitemapService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Like robots.txt, a path the standard sets: no underscore. Every page in it,
     * or, past what one file may list, the index of the files that do.
     */
    #[Route(
        path: '/sitemap.xml',
        name: self::ROUTE_SITEMAP,
        methods: ['GET', 'HEAD']
    )]
    public function sitemap(): Response
    {
        $entries = $this->getEntries();

        if (! $this->sitemapService->isSplit($entries)) {
            return $this->createResponse($this->renderUrlSet($entries));
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $index = $document->createElement('sitemapindex');
        $index->setAttribute('xmlns', self::NAMESPACE);
        $document->appendChild($index);

        for ($file = 1; $file <= $this->sitemapService->getFileCount($entries); ++$file) {
            $sitemap = $document->createElement('sitemap');
            $loc = $document->createElement('loc');
            $loc->appendChild($document->createTextNode(
                $this->urlGenerator->generate(self::ROUTE_SITEMAP_FILE, ['file' => $file], UrlGeneratorInterface::ABSOLUTE_URL)
            ));
            $sitemap->appendChild($loc);
            $index->appendChild($sitemap);
        }

        return $this->createResponse($document->saveXML());
    }

    /**
     * One file of a split sitemap, from 1: none when it is not split.
     */
    #[Route(
        path: '/sitemap-{file}.xml',
        name: self::ROUTE_SITEMAP_FILE,
        requirements: ['file' => '[1-9]\d*'],
        methods: ['GET', 'HEAD']
    )]
    public function file(int $file): Response
    {
        $entries = $this->getEntries();

        if (! $this->sitemapService->isSplit($entries) || $file > $this->sitemapService->getFileCount($entries)) {
            throw new NotFoundHttpException();
        }

        return $this->createResponse($this->renderUrlSet($this->sitemapService->getFileEntries($entries, $file)));
    }

    /**
     * @return SitemapEntry[]
     */
    private function getEntries(): array
    {
        if (! $this->sitemapService->isEnabled()) {
            throw new NotFoundHttpException('The sitemap of this app is not served by symfony-seo.');
        }

        return $this->sitemapService->getEntries();
    }

    /**
     * @param SitemapEntry[] $entries
     */
    private function renderUrlSet(array $entries): string
    {
        // Namespaces declared once, on the root: thousands of links carry only
        // their prefix.
        $document = new \DOMDocument('1.0', 'UTF-8');
        $urlset = $document->createElement('urlset');
        $urlset->setAttribute('xmlns', self::NAMESPACE);
        $urlset->setAttribute('xmlns:xhtml', self::NAMESPACE_XHTML);
        $document->appendChild($urlset);

        foreach ($entries as $entry) {
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

        return $document->saveXML();
    }

    private function createResponse(string $xml): Response
    {
        return new Response(
            $xml,
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}
