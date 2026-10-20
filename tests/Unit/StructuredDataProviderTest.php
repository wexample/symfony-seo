<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;
use Wexample\SymfonySeo\Normalizer\SchemaOrgFallbackNormalizer;
use Wexample\SymfonySeo\Normalizer\SchemaOrgNormalizer;
use Wexample\SymfonySeo\Service\StructuredDataProvider;
use Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg\FixtureFieldReader;
use Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg\FixturePerson;
use Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg\FixtureWork;

class StructuredDataProviderTest extends TestCase
{
    private const array DOCUMENT = ['title' => 'Home', 'description' => null, 'url' => null, 'subject' => null];

    public function testTheContextIsAddedWhereItIsLeftOut(): void
    {
        $provider = $this->provider([
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
        $this->assertSame([], $this->provider([])->getHeadStructuredData(self::DOCUMENT));
    }

    public function testARelativeIdIsTheSiteOfTheRequest(): void
    {
        $objects = $this->provider([['@type' => 'Person', '@id' => '/#person']])
            ->getHeadStructuredData(self::DOCUMENT);

        $this->assertSame('https://example.com/#person', $objects[0]['@id']);
    }

    public function testThePageSubjectIsDescribedFromItsEntity(): void
    {
        $work = new FixtureWork();
        $work->author = new FixturePerson();
        $work->techs = ['Symfony', 'Vue'];

        $subject = $this->provider([])->getHeadStructuredData(
            ['url' => 'https://example.com/fr/works/one', 'subject' => $work] + self::DOCUMENT
        )[0];

        $this->assertSame([
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            '@id' => 'https://example.com/fr/works/one#subject',
            // Read through the field reader, in the request's language.
            'name' => 'Title in fr',
            // A nested entity described in turn; no summary, no description.
            'author' => ['@type' => 'Person', 'name' => 'Jane Doe'],
            'keywords' => ['Symfony', 'Vue'],
            'publisher' => ['@id' => 'https://example.com/#person'],
            'url' => 'https://example.com/fr/works/one',
            'inLanguage' => 'fr',
        ], $subject);
    }

    public function testADatePropertyIsWrittenAsSchemaOrgReadsIt(): void
    {
        $work = new FixtureWork();

        $work->created = 2022;
        $this->assertSame('2022', $this->provider([])->getHeadStructuredData(['subject' => $work] + self::DOCUMENT)[0]['dateCreated']);

        $work->created = new \DateTimeImmutable('2022-03-14 10:30:00');
        $this->assertSame('2022-03-14', $this->provider([])->getHeadStructuredData(['subject' => $work] + self::DOCUMENT)[0]['dateCreated']);
    }

    public function testAnObjectMetAgainIsReferredTo(): void
    {
        $work = new FixtureWork();
        $work->author = new FixturePerson();
        $work->author->work = $work;

        $subject = $this->provider([])->getHeadStructuredData(
            ['url' => 'https://example.com/fr/works/one', 'subject' => $work] + self::DOCUMENT
        )[0];

        $this->assertSame(['@id' => 'https://example.com/fr/works/one#subject'], $subject['author']['subjectOf']);
    }

    public function testAnObjectNothingDescribesIsLeftOut(): void
    {
        $work = new FixtureWork();
        $work->cover = new \stdClass();

        $subject = $this->provider([])->getHeadStructuredData(['subject' => $work] + self::DOCUMENT)[0];

        $this->assertArrayNotHasKey('image', $subject);
    }

    public function testAPageWithoutSubjectAddsNothing(): void
    {
        $this->assertSame([], $this->provider([])->getHeadStructuredData(
            ['subject' => new \stdClass()] + self::DOCUMENT
        ));
    }

    private function provider(array $objects): StructuredDataProvider
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/fr/works/one');
        $request->setLocale('fr');
        $requestStack->push($request);

        return new StructuredDataProvider(
            $objects,
            new Serializer([
                new SchemaOrgNormalizer([new FixtureFieldReader()]),
                new SchemaOrgFallbackNormalizer(),
            ]),
            $requestStack,
        );
    }
}
