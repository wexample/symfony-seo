<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyHelpers\Interface\PathLocalizerInterface;
use Wexample\SymfonySeo\Service\RobotsService;
use Wexample\SymfonySeo\Tests\Fixtures\Robots\PrivateRobotsProvider;
use Wexample\SymfonySeo\Tests\Fixtures\Robots\SilentRobotsProvider;
use Wexample\SymfonySeo\Tests\Fixtures\Robots\TunnelRobotsProvider;

class RobotsServiceTest extends TestCase
{
    public function testRulesAreGroupedByUserAgentWithAllowBeforeDisallow(): void
    {
        $service = $this->createService([
            new PrivateRobotsProvider(),
            new SilentRobotsProvider(),
            new TunnelRobotsProvider(),
        ]);

        $this->assertSame(
            "User-agent: *\n"
            ."Allow: /private/public/\n"
            ."Disallow: /private/\n"
            ."Disallow: /tunnel/\n"
            ."\n"
            ."User-agent: Googlebot\n"
            ."Disallow: /no-google/\n",
            $service->buildBody()
        );
    }

    public function testAProviderWithoutRulesLeavesNoEmptyBlock(): void
    {
        $this->assertSame(
            '',
            $this->createService([new SilentRobotsProvider()])->buildBody()
        );
    }

    public function testSitemapsCloseTheFileAfterTheExtraLines(): void
    {
        $service = $this->createService(
            [new TunnelRobotsProvider()],
            sitemaps: ['https://example.com/sitemap.xml'],
            extra: "Crawl-delay: 10\n",
        );

        $this->assertSame(
            "User-agent: *\n"
            ."Disallow: /tunnel/\n"
            ."\n"
            ."Crawl-delay: 10\n"
            ."\n"
            ."Sitemap: https://example.com/sitemap.xml\n",
            $service->buildBody()
        );
    }

    public function testDisallowAllOverridesEverything(): void
    {
        $service = $this->createService(
            [new PrivateRobotsProvider()],
            disallowAll: true,
            sitemaps: ['https://example.com/sitemap.xml'],
            extra: 'Crawl-delay: 10',
        );

        $this->assertSame(
            "User-agent: *\nDisallow: /\n",
            $service->buildBody()
        );
    }

    public function testAPathIsDisallowedByTheLongestMatchingRule(): void
    {
        $service = $this->createService([new PrivateRobotsProvider()]);

        $this->assertTrue($service->isDisallowed('/private/page'));
        $this->assertFalse($service->isDisallowed('/private/public/page'));
        $this->assertFalse($service->isDisallowed('/elsewhere'));
        // Another user-agent's rules are not every crawler's.
        $this->assertFalse($service->isDisallowed('/no-google/page'));
    }

    public function testRulesFollowThePathsTheAppActuallyServes(): void
    {
        // What symfony-translations does: every path under each language.
        $localizer = new class () implements PathLocalizerInterface {
            public function localizePath(string $path): array
            {
                return ['/en'.$path, '/fr'.$path];
            }
        };

        $service = $this->createService([new TunnelRobotsProvider()], localizers: [$localizer]);

        $this->assertSame(
            "User-agent: *\nDisallow: /en/tunnel/\nDisallow: /fr/tunnel/\n",
            $service->buildBody()
        );
        $this->assertTrue($service->isDisallowed('/fr/tunnel/step'));
        $this->assertFalse($service->isDisallowed('/tunnel/step'));
    }

    private function createService(
        array $providers,
        bool $disallowAll = false,
        array $sitemaps = [],
        string $extra = '',
        array $localizers = [],
    ): RobotsService {
        return new RobotsService(
            providers: $providers,
            enabled: true,
            disallowAll: $disallowAll,
            sitemaps: $sitemaps,
            extra: $extra,
            localizers: $localizers,
        );
    }
}
