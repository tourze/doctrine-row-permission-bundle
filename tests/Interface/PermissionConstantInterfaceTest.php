<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Interface;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class PermissionConstantInterfaceTest extends TestCase
{
    /**
     * 测试权限常量是否正确定义
     */
    public function testPermissionConstants(): void
    {
        $this->assertEquals('view', PermissionConstantInterface::VIEW);
        $this->assertEquals('edit', PermissionConstantInterface::EDIT);
        $this->assertEquals('unlink', PermissionConstantInterface::UNLINK);
        $this->assertEquals('deny', PermissionConstantInterface::DENY);
    }
} 