<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;

class UserRowPermissionRepositoryTest extends TestCase
{
    private ManagerRegistry $registry;
    private EntityManagerInterface $entityManager;
    private UserInterface $user;
    private QueryBuilder $queryBuilder;
    private Query $query;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->willReturn($this->entityManager);
        
        $this->repository = new UserRowPermissionRepository($this->registry);
        
        $this->user = $this->createMock(UserInterface::class);
        
        // 设置查询构建器
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        
        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }
    
    /**
     * 测试 findPermission 方法
     */
    public function testFindPermission(): void
    {
        $entityClass = 'TestEntity';
        $entityId = '123';
        $expectedPermission = $this->createMock(UserRowPermission::class);
        
        // 使用 mock 替代实际的方法调用，以避免需要设置数据库连接
        $repository = $this->getMockBuilder(UserRowPermissionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'entityClass' => $entityClass,
                'entityId' => $entityId,
                'valid' => true,
            ])
            ->willReturn($expectedPermission);
        
        $result = $repository->findPermission($this->user, $entityClass, $entityId);
        
        $this->assertSame($expectedPermission, $result);
    }
    
    /**
     * 测试 findByPermission 方法使用有效的权限类型
     */
    public function testFindByPermissionWithValidPermissionType(): void
    {
        $entityClass = 'TestEntity';
        $entityId = '123';
        $permission = PermissionConstantInterface::VIEW;
        $expectedPermission = $this->createMock(UserRowPermission::class);
        
        // 使用 mock 替代实际的方法调用
        $repository = $this->getMockBuilder(UserRowPermissionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'entityClass' => $entityClass,
                'entityId' => $entityId,
                $permission => true,
                'valid' => true,
            ])
            ->willReturn($expectedPermission);
        
        $result = $repository->findByPermission($this->user, $entityClass, $entityId, $permission);
        
        $this->assertSame($expectedPermission, $result);
    }
    
    /**
     * 测试 findByPermission 方法使用无效的权限类型返回 null
     */
    public function testFindByPermissionWithInvalidPermissionType(): void
    {
        $entityClass = 'TestEntity';
        $entityId = '123';
        $permission = 'invalid_permission';
        
        // 使用 mock 替代实际的方法调用
        $repository = $this->getMockBuilder(UserRowPermissionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        
        // 断言 findOneBy 不会被调用
        $repository->expects($this->never())->method('findOneBy');
        
        $result = $repository->findByPermission($this->user, $entityClass, $entityId, $permission);
        
        $this->assertNull($result);
    }
    
    /**
     * 测试 findBatchPermissions 方法
     */
    public function testFindBatchPermissions(): void
    {
        $entityClass = 'TestEntity';
        $entityIds = ['123', '456', '789'];
        $expectedResult = [
            $this->createMock(UserRowPermission::class),
            $this->createMock(UserRowPermission::class)
        ];
        
        // 创建部分 mock，只重写 createQueryBuilder 方法
        $partialRepository = $this->getMockBuilder(UserRowPermissionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        
        // 设置预期行为
        $partialRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($this->queryBuilder);
        
        // 设置查询构建器预期
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('p.user = :user')
            ->willReturnSelf();
        
        $this->queryBuilder->expects($this->exactly(3))
            ->method('andWhere')
            ->willReturnSelf();
        
        $this->queryBuilder->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();
        
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedResult);
        
        // 调用方法并测试结果
        $result = $partialRepository->findBatchPermissions($this->user, $entityClass, $entityIds);
        
        $this->assertSame($expectedResult, $result);
    }
} 