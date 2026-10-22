<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\Robots;

use Wexample\SymfonySeo\Interface\RobotsProviderInterface;

class SilentRobotsProvider implements RobotsProviderInterface
{
    public function getRobotsRules(): array
    {
        return [];
    }
}
