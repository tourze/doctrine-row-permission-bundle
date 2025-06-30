<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineRowPermissionBundle\DoctrineRowPermissionBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineRowPermissionBundleTest extends TestCase
{
    public function testBundleExtendsSymfonyBundle(): void
    {
        $bundle = new DoctrineRowPermissionBundle();
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
}