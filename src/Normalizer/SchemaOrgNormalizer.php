<?php

namespace Wexample\SymfonySeo\Normalizer;

use Doctrine\Persistence\Proxy;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Wexample\PhpSchemaOrg\Class\SchemaOrgVocabulary;
use Wexample\SymfonyHelpers\Interface\EntityFieldReaderInterface;
use Wexample\SymfonyHelpers\Interface\HeadStructuredDataProviderInterface;
use Wexample\SymfonySeo\Attribute\SchemaOrg;
use Wexample\SymfonySeo\Attribute\SchemaOrgProperty;
use Wexample\SymfonySeo\Helper\SchemaOrgHelper;

/**
 * Any #[SchemaOrg] entity as a schema.org object, its fields read through the
 * EntityFieldReaderInterface services — in the visitor's language —, written
 * as schema.org's vocabulary says their property reads them, and what is an
 * object handed back to the serializer: a nested entity is described by this
 * same normalizer, a media by symfony-media's.
 */
final class SchemaOrgNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /** The `@id` of the object being described, the page's subject having one. */
    public const string CONTEXT_ID = 'schema_org_id';

    /** Where a relative `@id` of #[SchemaOrg]'s `extra` is resolved. */
    public const string CONTEXT_BASE_URL = 'schema_org_base_url';

    /** The language the fields are read in; the current one if omitted. */
    public const string CONTEXT_LOCALE = 'schema_org_locale';

    /**
     * Shared by every object of one description: one met again is not written
     * twice but referred to by its `@id`, or left out without one — which also
     * cuts a cycle.
     */
    public const string CONTEXT_SEEN = 'schema_org_seen';

    /**
     * @param iterable<EntityFieldReaderInterface> $fieldReaders
     */
    public function __construct(
        private readonly SchemaOrgVocabulary $vocabulary,
        #[AutowireIterator(EntityFieldReaderInterface::TAG)]
        private readonly iterable $fieldReaders = [],
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return HeadStructuredDataProviderInterface::FORMAT_SCHEMA_ORG === $format
            && is_object($data)
            && null !== self::schemaOf($data);
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): ?array {
        $seen = $context[self::CONTEXT_SEEN] ??= new \ArrayObject();
        $key = spl_object_id($data);

        if ($seen->offsetExists($key)) {
            return $seen[$key] ? ['@id' => $seen[$key]] : null;
        }

        $id = $context[self::CONTEXT_ID] ?? null;
        $seen[$key] = $id;
        $schema = self::schemaOf($data);
        $properties = self::propertiesOf($data);
        $this->assertDescribable($data, $schema, $properties);
        $object = ['@type' => $schema->type] + ($id ? ['@id' => $id] : []);

        // What the fields hold is described without the subject's `@id`.
        $nested = [self::CONTEXT_ID => null] + $context;

        foreach ($properties as $field => $name) {
            $value = $this->describe(
                $name,
                $this->read($data, $field, $context[self::CONTEXT_LOCALE] ?? null),
                $format,
                $nested
            );

            if (null !== $value && [] !== $value && '' !== $value) {
                $object[$name] = $value;
            }
        }

        foreach ($schema->extra as $name => $value) {
            $object[$name] ??= SchemaOrgHelper::resolveIds($value, $context[self::CONTEXT_BASE_URL] ?? null);
        }

        return $object;
    }

    /**
     * What a field holds as its property reads it — a year as a Date, a
     * moment with its time where a DateTime is accepted (SchemaOrgVocabulary)
     * —, each item of a list in turn, an object handed to the serializer.
     */
    private function describe(string $name, mixed $value, ?string $format, array $context): mixed
    {
        $value = $this->vocabulary->write($name, $value);

        if (is_iterable($value)) {
            $items = [];

            foreach ($value as $item) {
                if (null !== $item = $this->describe($name, $item, $format, $context)) {
                    $items[] = $item;
                }
            }

            return $items;
        }

        return is_object($value)
            ? $this->normalizer->normalize($value, $format, $context)
            : $value;
    }

    /**
     * A type or a property schema.org does not have is a mistake of the
     * entity's code, told as such rather than printed for the engines to
     * ignore.
     *
     * @param array<string, string> $properties
     */
    private function assertDescribable(object $entity, SchemaOrg $schema, array $properties): void
    {
        $violations = $this->vocabulary->getViolations(
            ['@type' => $schema->type] + array_fill_keys($properties, null) + $schema->extra
        );

        if ([] !== $violations) {
            throw new \LogicException(sprintf(
                '%s is described in words schema.org %s does not have: %s',
                $entity instanceof Proxy ? get_parent_class($entity) : $entity::class,
                $this->vocabulary->getVersion(),
                implode(' ', $violations)
            ));
        }
    }

    private function read(object $entity, string $field, ?string $locale): mixed
    {
        foreach ($this->fieldReaders as $reader) {
            if ($reader->supportsField($entity, $field)) {
                return $reader->readField($entity, $field, $locale);
            }
        }

        foreach (['get', 'is', 'has'] as $prefix) {
            if (method_exists($entity, $getter = $prefix.ucfirst($field))) {
                return $entity->$getter();
            }
        }

        return (new \ReflectionProperty($entity, $field))->getValue($entity);
    }

    private static function schemaOf(object $entity): ?SchemaOrg
    {
        $reflection = new \ReflectionClass($entity instanceof Proxy ? get_parent_class($entity) : $entity);

        do {
            if ($attribute = $reflection->getAttributes(SchemaOrg::class)[0] ?? null) {
                return $attribute->newInstance();
            }
        } while ($reflection = $reflection->getParentClass());

        return null;
    }

    /**
     * @return array<string, string> schema.org name by field, the class's and
     *                               its parents'
     */
    private static function propertiesOf(object $entity): array
    {
        $properties = [];
        $reflection = new \ReflectionClass($entity instanceof Proxy ? get_parent_class($entity) : $entity);

        do {
            foreach ($reflection->getProperties() as $property) {
                if ($attribute = $property->getAttributes(SchemaOrgProperty::class)[0] ?? null) {
                    $properties[$property->getName()] ??= $attribute->newInstance()->name;
                }
            }
        } while ($reflection = $reflection->getParentClass());

        return $properties;
    }
}
