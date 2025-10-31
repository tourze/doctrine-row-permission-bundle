<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineRowPermissionBundle\DependencyInjection\DoctrineRowPermissionExtension;
use Tourze\DoctrineRowPermissionBundle\Service\CondManager;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineRowPermissionExtension::class)]
final class DoctrineRowPermissionExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private DoctrineRowPermissionExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new DoctrineRowPermissionExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    /**
     * 测试扩展加载
     */
    public function testLoadLoadsServices(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务是否已注册
        $this->assertTrue($this->container->hasDefinition(CondManager::class));
        $this->assertTrue($this->container->hasDefinition(SecurityService::class));
    }

    /**
     * 测试扩展别名
     */
    public function testGetAlias(): void
    {
        $alias = $this->extension->getAlias();
        $this->assertEquals('doctrine_row_permission', $alias);
    }
}
