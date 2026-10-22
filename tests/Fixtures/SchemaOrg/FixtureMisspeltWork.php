<?php

namespace Wexample\SymfonySeo\Tests\Fixtures\SchemaOrg;

use Wexample\SymfonySeo\Attribute\SchemaOrg;
use Wexample\SymfonySeo\Attribute\SchemaOrgProperty;

#[SchemaOrg('CreativeWork')]
class FixtureMisspeltWork
{
    #[SchemaOrgProperty('dateCreatd')]
    public int $created = 2022;
}
