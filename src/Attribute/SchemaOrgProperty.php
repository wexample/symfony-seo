<?php

namespace Wexample\SymfonySeo\Attribute;

/**
 * The schema.org property a field of a #[SchemaOrg] entity is: its title the
 * `name`, its cover the `image`. Read as the visitor would read it, translated
 * where symfony-translations translates it; an entity in it is described in
 * turn.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SchemaOrgProperty
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
