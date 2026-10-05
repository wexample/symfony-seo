<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Wexample\SymfonyHelpers\Interface\HeadMetaProviderInterface;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

/**
 * An app closed to search engines (`robots.disallow_all`) says it on every
 * page too: robots.txt keeps a crawler from fetching the pages, not a search
 * engine from listing an address it found elsewhere — a link in a mail, a
 * chat. Only the page itself can ask not to be listed.
 */
class RobotsMetaProvider implements HeadMetaProviderInterface
{
    public function __construct(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_ROBOTS_DISALLOW_ALL)]
        private readonly bool $disallowAll,
    ) {
    }

    public function getHeadMeta(array $document): array
    {
        return $this->disallowAll
            ? [['name' => 'robots', 'content' => 'noindex, nofollow']]
            : [];
    }
}
