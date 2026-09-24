<?php

namespace Wexample\SymfonySeo\Class;

/**
 * What one provider asks of one user-agent. Several rules on the same user-agent,
 * from one provider or many, end up in a single block of the file.
 */
readonly class RobotsRule
{
    final public const string USER_AGENT_ALL = '*';

    /**
     * @param string[] $disallow
     * @param string[] $allow
     */
    public function __construct(
        public array $disallow = [],
        public array $allow = [],
        public string $userAgent = self::USER_AGENT_ALL,
    ) {
    }

    public function isEmpty(): bool
    {
        return empty($this->disallow) && empty($this->allow);
    }
}
