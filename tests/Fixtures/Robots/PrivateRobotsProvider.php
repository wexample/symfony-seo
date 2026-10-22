<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\Robots;

use Wexample\SymfonySeo\Class\RobotsRule;
use Wexample\SymfonySeo\Interface\RobotsProviderInterface;

class PrivateRobotsProvider implements RobotsProviderInterface
{
    public function getRobotsRules(): array
    {
        return [
            new RobotsRule(
                disallow: ['/private/'],
                allow: ['/private/public/'],
            ),
            new RobotsRule(
                disallow: ['/no-google/'],
                userAgent: 'Googlebot',
            ),
        ];
    }
}
