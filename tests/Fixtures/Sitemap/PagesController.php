<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\Sitemap;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class PagesController
{
    #[Route(
        path: '/{_locale}/page',
        name: 'page',
        requirements: ['_locale' => 'en|fr'],
        defaults: ['_locale' => 'en']
    )]
    public function page(): Response
    {
        return new Response();
    }

    #[Route(path: '/plain', name: 'plain')]
    public function plain(): Response
    {
        return new Response();
    }

    #[Route(path: '/private/hidden', name: 'private')]
    public function private(): Response
    {
        return new Response();
    }

    #[Route(path: '/excluded', name: 'excluded')]
    public function excluded(): Response
    {
        return new Response();
    }

    #[Route(path: '/item/{id}', name: 'item')]
    public function item(): Response
    {
        return new Response();
    }

    #[Route(path: '/form', name: 'form', methods: ['POST'])]
    public function form(): Response
    {
        return new Response();
    }

    #[Route(path: '/redirect', name: 'redirect', options: ['sitemap' => false])]
    public function redirect(): Response
    {
        return new Response();
    }

    #[Route(path: '/account', name: 'account')]
    #[IsGranted('ROLE_USER')]
    public function account(): Response
    {
        return new Response();
    }
}
