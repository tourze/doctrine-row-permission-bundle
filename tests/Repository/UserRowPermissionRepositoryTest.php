<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Repository;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class UserRowPermissionRepositoryTest extends TestCase
{
    private ManagerRegistry $registry;
    private UserInterface $user;
    private UserRowPermissionRepository $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->repository = new UserRowPermissionRepository($this->registry);
    }
    
    /**
     * 测试 findPermission 方法的参数验证
     */
    public function testFindPermissionParameterValidation(): void
    {
        // 测试方法签名
        $reflection = new \ReflectionMethod($this->repository, 'findPermission');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('entityClass', $parameters[1]->getName());
        $this->assertEquals('entityId', $parameters[2]->getName());
    }
    
    /**
     * 测试 findByPermission 方法参数验证
     */
    public function testFindByPermissionParameterValidation(): void
    {
        // 测试方法签名
        $reflection = new \ReflectionMethod($this->repository, 'findByPermission');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(4, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('entityClass', $parameters[1]->getName());
        $this->assertEquals('entityId', $parameters[2]->getName());
        $this->assertEquals('permission', $parameters[3]->getName());
    }
    
    /**
     * 测试 findByPermission 方法使用无效的权限类型返回 null
     */
    public function testFindByPermissionWithInvalidPermissionType(): void
    {
        $entityClass = 'TestEntity';
        $entityId = '123';
        $permission = 'invalid_permission';
        
        // 测试实际的方法实现 - 无效权限类型应该返回 null
        $result = $this->repository->findByPermission($this->user, $entityClass, $entityId, $permission);
        
        $this->assertNull($result);
    }
    
    /**
     * 测试 findBatchPermissions 方法参数验证
     */
    public function testFindBatchPermissionsParameterValidation(): void
    {
        // 测试方法签名
        $reflection = new \ReflectionMethod($this->repository, 'findBatchPermissions');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(3, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('entityClass', $parameters[1]->getName());
        $this->assertEquals('entityIds', $parameters[2]->getName());
        
        // 验证参数类型
        $paramType = $parameters[2]->getType();
        if ($paramType instanceof \ReflectionNamedType) {
            $this->assertEquals('array', $paramType->getName());
        }
    }
    
    /**
     * 测试有效权限类型的常量
     */
    public function testValidPermissionTypes(): void
    {
        $validPermissions = [
            PermissionConstantInterface::VIEW,
            PermissionConstantInterface::EDIT,
            PermissionConstantInterface::UNLINK,
            PermissionConstantInterface::DENY,
        ];
        
        // 测试权限常量定义是否正确
        $this->assertContains('view', $validPermissions);
        $this->assertContains('edit', $validPermissions);
        $this->assertContains('unlink', $validPermissions);
        $this->assertContains('deny', $validPermissions);
    }
}