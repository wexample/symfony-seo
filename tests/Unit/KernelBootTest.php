<?php

namespace Wexample\SymfonySeo\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonySeo\WexampleSymfonySeoBundle;

class KernelBootTest extends KernelTestCase
{
    public function testKernelBootsWithBundle(): void
    {
        self::bootKernel();

        $this->assertInstanceOf(
            WexampleSymfonySeoBundle::class,
            self::$kernel->getBundles()['WexampleSymfonySeoBundle'] ?? null
        );
    }
}
