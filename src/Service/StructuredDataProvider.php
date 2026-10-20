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
 * rich result are drawn from. A page about an entity (`document_subject`) adds
 * that entity, described by its #[SchemaOrg].
 */
class StructuredDataProvider implements HeadStructuredDataProviderInterface
{
    public function __construct(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_STRUCTURED_DATA)]
        private readonly array $objects,
        private readonly NormalizerInterface $normalizer,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getHeadStructuredData(array $document): array
    {
        $baseUrl = $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost();
        $objects = array_map(
            static fn (array $object): array => SchemaOrgHelper::resolveIds($object, $baseUrl),
            array_values(array_filter($this->objects, 'is_array'))
        );

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

        return $object + array_filter([
            'url' => $url,
            'inLanguage' => $request?->getLocale(),
        ]);
    }
}
