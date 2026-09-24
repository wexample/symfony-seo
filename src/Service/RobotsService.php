<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Wexample\SymfonySeo\Class\RobotsRule;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;
use Wexample\SymfonySeo\Interface\RobotsProviderInterface;

class RobotsService
{
    final public const string TAG_PROVIDER = 'wexample_symfony_seo.robots_provider';

    /**
     * @param iterable<RobotsProviderInterface> $providers
     * @param string[] $sitemaps
     */
    public function __construct(
        #[AutowireIterator(self::TAG_PROVIDER)]
        private readonly iterable $providers,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_ROBOTS_ENABLED)]
        private readonly bool $enabled,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_ROBOTS_DISALLOW_ALL)]
        private readonly bool $disallowAll,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_ROBOTS_SITEMAPS)]
        private readonly array $sitemaps,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_ROBOTS_EXTRA)]
        private readonly string $extra,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function buildBody(): string
    {
        if ($this->disallowAll) {
            return $this->renderGroup(RobotsRule::USER_AGENT_ALL, [], ['/']);
        }

        $sections = [];

        foreach ($this->collectGroups() as $group) {
            $sections[] = $this->renderGroup($group['userAgent'], $group['allow'], $group['disallow']);
        }

        if ('' !== $extra = trim($this->extra)) {
            $sections[] = $extra."\n";
        }

        // Sitemaps belong to no user-agent: kept apart, at the end.
        if (! empty($this->sitemaps)) {
            $sections[] = implode('', array_map(
                static fn (string $sitemap): string => 'Sitemap: '.$sitemap."\n",
                $this->sitemaps
            ));
        }

        return implode("\n", $sections);
    }

    /**
     * One group per user-agent, in the order they were first declared. User-agents
     * are matched case-insensitively by crawlers, so they are grouped the same way.
     *
     * @return array<string, array{userAgent: string, allow: string[], disallow: string[]}>
     */
    private function collectGroups(): array
    {
        $groups = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->getRobotsRules() as $rule) {
                if ($rule->isEmpty()) {
                    continue;
                }

                $key = strtolower($rule->userAgent);
                $groups[$key] ??= [
                    'userAgent' => $rule->userAgent,
                    'allow' => [],
                    'disallow' => [],
                ];

                $groups[$key]['allow'] = array_merge($groups[$key]['allow'], $rule->allow);
                $groups[$key]['disallow'] = array_merge($groups[$key]['disallow'], $rule->disallow);
            }
        }

        return $groups;
    }

    /**
     * Allow comes first: on overlapping paths that is what Google and Bing read.
     *
     * @param string[] $allow
     * @param string[] $disallow
     */
    private function renderGroup(
        string $userAgent,
        array $allow,
        array $disallow
    ): string {
        $lines = ['User-agent: '.$userAgent];

        foreach (array_unique($allow) as $path) {
            $lines[] = 'Allow: '.$path;
        }

        foreach (array_unique($disallow) as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        return implode("\n", $lines)."\n";
    }
}
