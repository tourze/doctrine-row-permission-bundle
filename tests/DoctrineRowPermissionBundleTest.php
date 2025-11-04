<?php

declare(strict_types=1);


namespace Tourze\DoctrineRowPermissionBundle\Tests {

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineRowPermissionBundle\DoctrineRowPermissionBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineRowPermissionBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineRowPermissionBundleTest extends AbstractBundleTestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new DoctrineRowPermissionBundle();
        $this->assertInstanceOf(DoctrineRowPermissionBundle::class, $bundle);
    }
}

}
