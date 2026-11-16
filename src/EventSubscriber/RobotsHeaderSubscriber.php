<?php

namespace Wexample\SymfonySeo\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

/**
 * An app closed to search engines (`robots.disallow_all`) says it on every
 * response, `X-Robots-Tag: noindex, nofollow`: the head's meta only reaches
 * the pages, and a file served by the app — a PDF, an image, a download —
 * has no head to carry it. A response that already says what it wants keeps
 * its own word.
 */
class RobotsHeaderSubscriber implements EventSubscriberInterface
{
    final public const string HEADER = 'X-Robots-Tag';

    final public const string VALUE = 'noindex, nofollow';

    public function __construct(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_ROBOTS_DISALLOW_ALL)]
        private readonly bool $disallowAll,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (! $this->disallowAll || ! $event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        if (! $headers->has(self::HEADER)) {
            $headers->set(self::HEADER, self::VALUE);
        }
    }
}
