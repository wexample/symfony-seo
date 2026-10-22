<?php

namespace Wexample\SymfonySeo\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Wexample\SymfonyHelpers\Interface\HeadStructuredDataProviderInterface;

/**
 * An object nothing describes in schema.org's words is left out of the
 * description, rather than dumped field by field by the serializer's
 * ObjectNormalizer. Registered just above it.
 */
final class SchemaOrgFallbackNormalizer implements NormalizerInterface
{
    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return HeadStructuredDataProviderInterface::FORMAT_SCHEMA_ORG === $format && is_object($data);
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): null {
        return null;
    }
}
