<?php

namespace Wexample\SymfonySeo\Attribute;

/**
 * What an entity is in schema.org's words, its page then telling the engines
 * so: a project a CreativeWork, a person a Person. Its fields are described by
 * #[SchemaOrgProperty]; `extra` adds what no field holds — an author kept in
 * configuration, referred to by its `@id` (relative to the site when it opens
 * on a slash).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SchemaOrg
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public readonly string $type,
        public readonly array $extra = [],
    ) {
    }
}
