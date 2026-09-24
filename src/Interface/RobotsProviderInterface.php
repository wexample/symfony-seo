<?php

namespace Wexample\SymfonySeo\Interface;

use Wexample\SymfonySeo\Class\RobotsRule;

/**
 * Implemented by any service that has paths to keep crawlers away from. Being
 * autoconfigured is enough for its rules to land in /robots.txt.
 */
interface RobotsProviderInterface
{
    /**
     * @return RobotsRule[]
     */
    public function getRobotsRules(): array;
}
