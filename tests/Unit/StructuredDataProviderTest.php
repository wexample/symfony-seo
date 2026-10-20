<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;
use Wexample\PhpSchemaOrg\Class\SchemaOrgVocabulary;
use Wexample\SymfonySeo\Normalizer\SchemaOrgFallbackNormalizer;
use Wexample\SymfonySeo\Normalizer\SchemaOrgNormalizer;
use Wexample\SymfonySeo\Service\StructuredDataProvider;
use Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg\FixtureFieldReader;
use Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg\FixtureMisspeltWork;
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

    public function testAFieldIsWrittenAsItsPropertyReadsIt(): void
    {
        $work = new FixtureWork();

        // A Date or a DateTime: a year a Date, a moment keeping its time.
        $work->created = 2022;
        $this->assertSame('2022', $this->provider([])->getHeadStructuredData(['subject' => $work] + self::DOCUMENT)[0]['dateCreated']);

        $work->created = new \DateTimeImmutable('2022-03-14 10:30:00+01:00');
        $this->assertSame('2022-03-14T10:30:00+01:00', $this->provider([])->getHeadStructuredData(['subject' => $work] + self::DOCUMENT)[0]['dateCreated']);
    }

    public function testAPropertySchemaOrgDoesNotHaveIsAMistake(): void
    {
        $this->expectExceptionMessage('"dateCreatd" is not a schema.org property.');

        $this->provider([])->getHeadStructuredData(['subject' => new FixtureMisspeltWork()] + self::DOCUMENT);
    }

    public function testAPageDescribedByItsRouteIsCompletedWithWhatThePageSays(): void
    {
        $objects = $this->provider([], ['about_index' => ['@type' => 'ProfilePage', 'mainEntity' => ['@id' => '/#person']]])
            ->getHeadStructuredData(['title' => 'About', 'url' => 'https://example.com/fr/about'] + self::DOCUMENT);

        $this->assertSame([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            '@id' => 'https://example.com/fr/about#page',
            'mainEntity' => ['@id' => 'https://example.com/#person'],
            'url' => 'https://example.com/fr/about',
            'name' => 'About',
            'inLanguage' => 'fr',
        ], $objects[0]);
    }

    public function testASubjectIsGivenOnlyThePropertiesItsTypeHas(): void
    {
        $product = new \stdClass();
        $serializer = new Serializer([new class () implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface {
            public function getSupportedTypes(?string $format): array
            {
                return ['object' => true];
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function normalize(mixed $data, ?string $format = null, array $context = []): array
            {
                return ['@type' => 'Product', 'name' => 'Ticket'];
            }
        }]);

        $subject = $this->provider([], serializer: $serializer)->getHeadStructuredData(
            ['url' => 'https://example.com/fr/shop/ticket', 'subject' => $product] + self::DOCUMENT
        )[0];

        $this->assertSame([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => 'https://example.com/fr/shop/ticket#subject',
            'name' => 'Ticket',
            // No inLanguage: a Product has none.
            'url' => 'https://example.com/fr/shop/ticket',
        ], $subject);
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

    private function provider(array $objects, array $pages = [], ?Serializer $serializer = null): StructuredDataProvider
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/fr/works/one');
        $request->setLocale('fr');
        $request->attributes->set('_route', 'about_index');
        $requestStack->push($request);
        $vocabulary = new SchemaOrgVocabulary();

        return new StructuredDataProvider(
            $objects,
            $serializer ?? new Serializer([
                new SchemaOrgNormalizer($vocabulary, [new FixtureFieldReader()]),
                new SchemaOrgFallbackNormalizer(),
            ]),
            $requestStack,
            $vocabulary,
            $pages,
        );
    }
}
