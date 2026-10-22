<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg;

use Wexample\SymfonySeo\Attribute\SchemaOrg;
use Wexample\SymfonySeo\Attribute\SchemaOrgProperty;

#[SchemaOrg('CreativeWork', extra: ['publisher' => ['@id' => '/#person']])]
class FixtureWork
{
    #[SchemaOrgProperty('name')]
    public string $title = 'Original title';

    #[SchemaOrgProperty('description')]
    public ?string $summary = null;

    #[SchemaOrgProperty('author')]
    public ?FixturePerson $author = null;

    #[SchemaOrgProperty('image')]
    public ?object $cover = null;

    #[SchemaOrgProperty('keywords')]
    public array $techs = [];

    /** A year, or a moment: what dateCreated takes, a Date or a DateTime. */
    #[SchemaOrgProperty('dateCreated')]
    public int|\DateTimeInterface|null $created = null;
}
