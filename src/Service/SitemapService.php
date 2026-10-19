<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Wexample\SymfonySeo\Class\SitemapEntry;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

/**
 * The pages a visitor can open, read off the routes rather than listed by
 * hand: those answering GET with no parameter to guess, in every language
 * their path offers. Left out: paths for programs (`/_…`, `/api`), files
 * (`/robots.txt`), pages behind a sign-in (`#[IsGranted]`), routes with the
 * option `sitemap: false`, what robots.txt keeps crawlers from, and what the
 * app excludes.
 */
class SitemapService
{
    private const string LOCALE = '_locale';
    // A route option any bundle may set, false to keep a route out — a
    // redirect, say — without depending on this one.
    final public const string OPTION = 'sitemap';
    // Read by name: the security bundle is not a dependency of this one.
    private const string IS_GRANTED = 'Symfony\\Component\\Security\\Http\\Attribute\\IsGranted';

    /**
     * @param string[] $exclude
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RobotsService $robotsService,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_SITEMAP_ENABLED)]
        private readonly bool $enabled,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_SITEMAP_EXCLUDE)]
        private readonly array $exclude,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return SitemapEntry[]
     */
    public function getEntries(): array
    {
        $entries = [];

        foreach ($this->router->getRouteCollection() as $name => $route) {
            if (! $this->isPage($route)) {
                continue;
            }

            $locales = $this->getLocales($route);

            if (empty($locales)) {
                $url = $this->router->generate($name, [], UrlGeneratorInterface::ABSOLUTE_URL);

                if ($this->isListed($url)) {
                    $entries[] = new SitemapEntry($url);
                }

                continue;
            }

            $alternates = [];

            foreach ($locales as $locale) {
                $alternates[str_replace('_', '-', $locale)] = $this->router->generate(
                    $name,
                    [self::LOCALE => $locale],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $default = $route->getDefault(self::LOCALE);

            if (in_array($default, $locales, true)) {
                $alternates['x-default'] = $alternates[str_replace('_', '-', $default)];
            }

            foreach ($locales as $locale) {
                $url = $alternates[str_replace('_', '-', $locale)];

                if ($this->isListed($url)) {
                    $entries[] = new SitemapEntry($url, $alternates);
                }
            }
        }

        return $entries;
    }

    /**
     * Opened by GET, and nothing in its path but the language to fill in.
     */
    private function isPage(Route $route): bool
    {
        if (false === $route->getOption(self::OPTION)) {
            return false;
        }

        $methods = $route->getMethods();

        if (! empty($methods) && ! in_array('GET', $methods, true)) {
            return false;
        }

        $path = $route->getPath();

        if (str_starts_with($path, '/_') || str_starts_with($path, '/api')) {
            return false;
        }

        // A file — robots.txt, favicon.ico, this sitemap — rather than a page.
        if (str_contains(basename($path), '.')) {
            return false;
        }

        foreach ($route->compile()->getPathVariables() as $variable) {
            if (self::LOCALE !== $variable && ! $route->hasDefault($variable)) {
                return false;
            }
        }

        return ! $this->isGuarded((string) $route->getDefault('_controller'));
    }

    /**
     * Whether the controller asks for a permission, on its class or its
     * method: a page a crawler would only be turned away from.
     */
    private function isGuarded(string $controller): bool
    {
        [$class, $method] = array_pad(explode('::', $controller, 2), 2, '__invoke');

        if (! class_exists($class)) {
            return false;
        }

        $reflection = new \ReflectionClass($class);

        if (! empty($reflection->getAttributes(self::IS_GRANTED))) {
            return true;
        }

        return $reflection->hasMethod($method)
            && ! empty($reflection->getMethod($method)->getAttributes(self::IS_GRANTED));
    }

    /**
     * The languages the path offers, from its requirement: `en|fr|pt_BR`.
     *
     * @return string[]
     */
    private function getLocales(Route $route): array
    {
        if (! in_array(self::LOCALE, $route->compile()->getPathVariables(), true)) {
            return [];
        }

        $requirement = (string) $route->getRequirement(self::LOCALE);

        return preg_match('/^[\w-]+(\|[\w-]+)*$/', $requirement)
            ? explode('|', $requirement)
            : [];
    }

    private function isListed(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($this->robotsService->isDisallowed($path)) {
            return false;
        }

        foreach ($this->exclude as $pattern) {
            if (preg_match('{'.$pattern.'}', $path)) {
                return false;
            }
        }

        return true;
    }
}
