<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg;

use Wexample\SymfonyHelpers\Interface\EntityFieldReaderInterface;

/**
 * Stands for symfony-translations: the title read in the asked language.
 */
class FixtureFieldReader implements EntityFieldReaderInterface
{
    public function supportsField(object $entity, string $field): bool
    {
        return 'title' === $field;
    }

    public function readField(object $entity, string $field, ?string $locale = null): mixed
    {
        return 'Title in '.$locale;
    }
}
