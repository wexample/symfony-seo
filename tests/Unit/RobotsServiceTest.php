<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
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

    private function createService(
        array $providers,
        bool $disallowAll = false,
        array $sitemaps = [],
        string $extra = '',
    ): RobotsService {
        return new RobotsService(
            providers: $providers,
            enabled: true,
            disallowAll: $disallowAll,
            sitemaps: $sitemaps,
            extra: $extra,
        );
    }
}
