<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg;

use Wexample\SymfonySeo\Attribute\SchemaOrg;
use Wexample\SymfonySeo\Attribute\SchemaOrgProperty;

#[SchemaOrg('Person')]
class FixturePerson
{
    #[SchemaOrgProperty('name')]
    public string $name = 'Jane Doe';

    // Back to the work: a cycle the description has to cut.
    #[SchemaOrgProperty('subjectOf')]
    public ?FixtureWork $work = null;
}
