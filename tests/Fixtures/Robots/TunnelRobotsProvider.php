<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\Robots;

use Wexample\SymfonySeo\Class\RobotsRule;
use Wexample\SymfonySeo\Interface\RobotsProviderInterface;

class TunnelRobotsProvider implements RobotsProviderInterface
{
    public function getRobotsRules(): array
    {
        return [
            new RobotsRule(disallow: ['/tunnel/']),
            // Declared again for another user-agent spelling, and empty: neither may
            // open a block of its own.
            new RobotsRule(userAgent: 'googlebot'),
        ];
    }
}
