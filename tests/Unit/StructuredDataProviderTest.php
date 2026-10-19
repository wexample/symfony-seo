<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonySeo\Service\StructuredDataProvider;

class StructuredDataProviderTest extends TestCase
{
    private const array DOCUMENT = ['title' => 'Home', 'description' => null, 'url' => null];

    public function testTheContextIsAddedWhereItIsLeftOut(): void
    {
        $provider = new StructuredDataProvider([
            ['@type' => 'Person', 'name' => 'Jane Doe'],
            ['@context' => 'https://example.org', '@type' => 'WebSite'],
        ]);

        $this->assertSame([
            ['@context' => 'https://schema.org', '@type' => 'Person', 'name' => 'Jane Doe'],
            ['@context' => 'https://example.org', '@type' => 'WebSite'],
        ], $provider->getHeadStructuredData(self::DOCUMENT));
    }

    public function testNothingDeclaredIsNothingPrinted(): void
    {
        $this->assertSame([], (new StructuredDataProvider([]))->getHeadStructuredData(self::DOCUMENT));
    }
}
