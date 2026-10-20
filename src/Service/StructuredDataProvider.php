<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Wexample\PhpSchemaOrg\Class\SchemaOrgVocabulary;
use Wexample\SymfonyHelpers\Interface\HeadStructuredDataProviderInterface;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;
use Wexample\SymfonySeo\Helper\SchemaOrgHelper;
use Wexample\SymfonySeo\Normalizer\SchemaOrgNormalizer;

/**
 * What the app declares of itself for the engines — the person or the company
 * behind it, the site —, the same on every page: what a knowledge panel and a
 * rich result are drawn from. A page the configuration describes by its route
 * adds itself — an about page a ProfilePage —, and a page about an entity
 * (`document_subject`) adds that entity, described by its normalizer.
 */
class StructuredDataProvider implements HeadStructuredDataProviderInterface
{
    public function __construct(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_STRUCTURED_DATA)]
        private readonly array $objects,
        private readonly NormalizerInterface $normalizer,
        private readonly RequestStack $requestStack,
        private readonly SchemaOrgVocabulary $vocabulary,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_STRUCTURED_DATA_PAGES)]
        private readonly array $pages = [],
    ) {
    }

    public function getHeadStructuredData(array $document): array
    {
        $baseUrl = $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost();
        $objects = array_map(
            static fn (array $object): array => SchemaOrgHelper::resolveIds($object, $baseUrl),
            array_values(array_filter($this->objects, 'is_array'))
        );

        if ($page = $this->describePage($document)) {
            $objects[] = $page;
        }

        if ($subject = $this->describeSubject($document)) {
            $objects[] = $subject;
        }

        return array_map(
            // First when added, as written: the context is read before the rest.
            static fn (array $object): array => isset($object['@context'])
                ? $object
                : ['@context' => SchemaOrgVocabulary::CONTEXT] + $object,
            $objects
        );
    }

    /**
     * The page itself, when the configuration describes its route: the object
     * configured, completed with its address, its title and its language.
     */
    private function describePage(array $document): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! is_array($page = $this->pages[$request?->attributes->get('_route')] ?? null)) {
            return null;
        }

        $url = $document['url'] ?? null;

        return $this->complete(
            SchemaOrgHelper::resolveIds($page, $request->getSchemeAndHttpHost()),
            $url ? $url.'#page' : null,
            ['url' => $url, 'name' => $document['title'] ?? null, 'inLanguage' => $request->getLocale()]
        );
    }

    /**
     * The entity the page is about, completed with what the page knows and the
     * entity does not: where it is read, and in which language.
     */
    private function describeSubject(array $document): ?array
    {
        if (! is_object($subject = $document['subject'] ?? null)
            || ! $this->normalizer->supportsNormalization($subject, self::FORMAT_SCHEMA_ORG)) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $url = $document['url'] ?? null;
        $object = $this->normalizer->normalize($subject, self::FORMAT_SCHEMA_ORG, [
            SchemaOrgNormalizer::CONTEXT_ID => $url ? $url.'#subject' : null,
            SchemaOrgNormalizer::CONTEXT_BASE_URL => $request?->getSchemeAndHttpHost(),
            SchemaOrgNormalizer::CONTEXT_LOCALE => $request?->getLocale(),
        ]);

        if (! is_array($object)) {
            return null;
        }

        return $this->complete($object, $url ? $url.'#subject' : null, [
            'url' => $url,
            'inLanguage' => $request?->getLocale(),
        ]);
    }

    /**
     * An object given the `@id` it does not have — a normalizer of another
     * bundle may not set it — and those of the properties it lacks its type
     * has: a Product has no `inLanguage`.
     *
     * @param array<string, mixed> $properties
     */
    private function complete(array $object, ?string $id, array $properties): array
    {
        if ($id && ! isset($object['@id'])) {
            $object = ['@type' => $object['@type'] ?? null, '@id' => $id] + $object;
        }

        foreach (array_filter($properties) as $name => $value) {
            foreach ((array) ($object['@type'] ?? []) as $type) {
                if ($this->vocabulary->isPropertyOf($name, $type)) {
                    $object[$name] ??= $value;

                    break;
                }
            }
        }

        return $object;
    }
}
