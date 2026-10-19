<?php

namespace Wexample\SymfonySeo\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

/**
 * Sends the visitors of a host the app answers on only to redirect them — a
 * former domain now pointed at the same server — to where they belong, what an
 * .htaccess rule used to do, declared with the app instead of on the server.
 */
class HostRedirectSubscriber implements EventSubscriberInterface
{
    // Before the router (32) and what adds the language to a path: a
    // redirected host is answered before the app reads anything of the request.
    public const int PRIORITY = 128;

    public function __construct(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_REDIRECT_HOSTS)]
        private readonly array $redirectHosts,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', self::PRIORITY]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest() || [] === $this->redirectHosts) {
            return;
        }

        $request = $event->getRequest();
        $redirect = $this->redirectHosts[$request->getHost()] ?? null;
        if (null === $redirect) {
            return;
        }

        $target = rtrim($redirect['to'], '/');
        $target .= $redirect['keep_path'] ? $request->getRequestUri() : '/';

        $event->setResponse(new RedirectResponse($target, $redirect['permanent'] ? 301 : 302));
    }
}
