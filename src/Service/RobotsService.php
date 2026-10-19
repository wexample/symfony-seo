<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Wexample\SymfonyHelpers\Interface\PathLocalizerInterface;
use Wexample\SymfonySeo\Class\RobotsRule;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;
use Wexample\SymfonySeo\Interface\RobotsProviderInterface;

class RobotsService
{
    final public const string TAG_PROVIDER = 'wexample_symfony_seo.robots_provider';

    /**
     * @param iterable<RobotsProviderInterface> $providers
     * @param string[] $sitemaps
     * @param iterable<PathLocalizerInterface> $localizers where the paths the
     *                                                     providers write are
     *                                                     actually served — under
     *                                                     each language's prefix
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
        #[AutowireIterator(PathLocalizerInterface::TAG)]
        private readonly iterable $localizers = [],
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param string[] $sitemaps added to the configured ones: the bundle's own
     *                           sitemap, whose address is only known per request
     */
    public function buildBody(array $sitemaps = []): string
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
        $sitemaps = array_unique([...$this->sitemaps, ...$sitemaps]);

        if (! empty($sitemaps)) {
            $sections[] = implode('', array_map(
                static fn (string $sitemap): string => 'Sitemap: '.$sitemap."\n",
                $sitemaps
            ));
        }

        return implode("\n", $sections);
    }

    /**
     * Whether a crawler reading the file for every user-agent (`*`) would keep
     * away from the path: a disallowed prefix matches it, and no longer allowed
     * one does — the longest rule wins, as Google and Bing read it.
     */
    public function isDisallowed(string $path): bool
    {
        if ($this->disallowAll) {
            return true;
        }

        $group = $this->collectGroups()[strtolower(RobotsRule::USER_AGENT_ALL)] ?? null;

        if (null === $group) {
            return false;
        }

        $longest = static fn (array $prefixes): int => max([-1, ...array_map(
            static fn (string $prefix): int => str_starts_with($path, $prefix) ? strlen($prefix) : -1,
            $prefixes
        )]);

        return $longest($group['disallow']) > $longest($group['allow']);
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

                $groups[$key]['allow'] = array_merge($groups[$key]['allow'], $this->localize($rule->allow));
                $groups[$key]['disallow'] = array_merge($groups[$key]['disallow'], $this->localize($rule->disallow));
            }
        }

        return $groups;
    }

    /**
     * The paths as the app serves them: a provider writes `/tunnels/plan/`,
     * the app may answer it at `/en/tunnels/plan/` and in every other language.
     *
     * @param string[] $paths
     *
     * @return string[]
     */
    private function localize(array $paths): array
    {
        foreach ($this->localizers as $localizer) {
            $localized = [];

            foreach ($paths as $path) {
                array_push($localized, ...$localizer->localizePath($path));
            }

            $paths = $localized;
        }

        return $paths;
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
