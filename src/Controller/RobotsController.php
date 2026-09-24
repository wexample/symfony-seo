<?php

namespace Wexample\SymfonySeo\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonySeo\Service\RobotsService;

#[AsController]
class RobotsController
{
    final public const string ROUTE_ROBOTS = 'seo_robots';

    /**
     * The path is set by the standard, not by who reads it: the one page a program
     * reads that does not open on an underscore.
     */
    #[Route(
        path: '/robots.txt',
        name: self::ROUTE_ROBOTS,
        methods: ['GET', 'HEAD']
    )]
    public function robots(RobotsService $robotsService): Response
    {
        if (! $robotsService->isEnabled()) {
            throw new NotFoundHttpException('The robots.txt of this app is not served by symfony-seo.');
        }

        return new Response(
            $robotsService->buildBody(),
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
