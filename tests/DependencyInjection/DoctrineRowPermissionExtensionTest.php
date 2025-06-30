<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineRowPermissionBundle\DependencyInjection\DoctrineRowPermissionExtension;
use Tourze\DoctrineRowPermissionBundle\Service\CondManager;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;

class DoctrineRowPermissionExtensionTest extends TestCase
{
    private DoctrineRowPermissionExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new DoctrineRowPermissionExtension();
        $this->container = new ContainerBuilder();
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