<?php

namespace Wexample\SymfonySeo\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

#[AsController]
class FaviconController
{
    final public const string ROUTE_FAVICON = 'seo_favicon';

    private const int MAX_AGE = 86400;

    /**
     * Only reached when the app has no public/favicon.ico: the web server serves the
     * static file first, which is what lets an app replace this one by just adding it.
     */
    #[Route(
        path: '/favicon.ico',
        name: self::ROUTE_FAVICON,
        methods: ['GET', 'HEAD']
    )]
    public function favicon(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_FAVICON_ENABLED)]
        bool $enabled,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_FAVICON_PATH)]
        string $path,
    ): BinaryFileResponse {
        if (! $enabled) {
            throw new NotFoundHttpException('The favicon of this app is not served by symfony-seo.');
        }

        $response = new BinaryFileResponse($path, headers: ['Content-Type' => 'image/x-icon']);
        $response->setPublic();
        $response->setMaxAge(self::MAX_AGE);

        return $response;
    }
}
