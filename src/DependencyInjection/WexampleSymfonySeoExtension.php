<?php

namespace Wexample\SymfonySeo\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonySeo\Interface\RobotsProviderInterface;
use Wexample\SymfonySeo\Service\RobotsService;

class WexampleSymfonySeoExtension extends AbstractWexampleSymfonyExtension
{
    public const string PARAMETER_ROBOTS_ENABLED = 'wexample_symfony_seo.robots.enabled';
    public const string PARAMETER_ROBOTS_DISALLOW_ALL = 'wexample_symfony_seo.robots.disallow_all';
    public const string PARAMETER_ROBOTS_SITEMAPS = 'wexample_symfony_seo.robots.sitemaps';
    public const string PARAMETER_ROBOTS_EXTRA = 'wexample_symfony_seo.robots.extra';
    public const string PARAMETER_FAVICON_ENABLED = 'wexample_symfony_seo.favicon.enabled';
    public const string PARAMETER_FAVICON_PATH = 'wexample_symfony_seo.favicon.path';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        // Providers live in the other bundles and in the apps: the tag has to follow
        // the interface wherever it is implemented. An #[AutoconfigureTag] on the
        // interface itself is not read.
        $container
            ->registerForAutoconfiguration(RobotsProviderInterface::class)
            ->addTag(RobotsService::TAG_PROVIDER);

        $config = $this->processConfiguration(
            new Configuration(),
            $configs
        );

        $container->setParameter(self::PARAMETER_ROBOTS_ENABLED, $config['robots']['enabled']);
        $container->setParameter(self::PARAMETER_ROBOTS_DISALLOW_ALL, $config['robots']['disallow_all']);
        $container->setParameter(self::PARAMETER_ROBOTS_SITEMAPS, $config['robots']['sitemaps']);
        $container->setParameter(self::PARAMETER_ROBOTS_EXTRA, $config['robots']['extra']);

        $container->setParameter(self::PARAMETER_FAVICON_ENABLED, $config['favicon']['enabled']);
        $container->setParameter(
            self::PARAMETER_FAVICON_PATH,
            $config['favicon']['path'] ?? __DIR__.'/../Resources/favicon/favicon.ico'
        );
    }
}
