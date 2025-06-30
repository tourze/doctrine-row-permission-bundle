<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Request\GrantRowPermissionRequest;
use Tourze\DoctrineRowPermissionBundle\Service\CondManager;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;

class SecurityServiceTest extends TestCase
{
    private UserRowPermissionRepository $repository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private CacheItemPoolInterface $cache;
    private CondManager $condManager;
    private SecurityService $service;
    private UserInterface $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRowPermissionRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->condManager = $this->createMock(CondManager::class);
        
        $this->service = new SecurityService(
            $this->repository,
            $this->entityManager,
            $this->logger,
            $this->condManager,
            $this->cache
        );
        
        $this->user = $this->createMock(UserInterface::class);
        $this->user->method('getUserIdentifier')->willReturn('test-user');
    }

    /**
     * 测试 hasPermission 方法对于 null 用户返回 false
     */
    public function testHasPermissionWithNullUser(): void
    {
        $entity = new \stdClass();
        
        $result = $this->service->hasPermission(null, $entity, PermissionConstantInterface::VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * 测试 hasPermission 方法对于没有 getId 方法的实体返回 false
     */
    public function testHasPermissionWithEntityWithoutGetId(): void
    {
        $entity = new \stdClass();
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('实体没有 getId 方法，无法检查权限', $this->arrayHasKey('entity_class'));
        
        $result = $this->service->hasPermission($this->user, $entity, PermissionConstantInterface::VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * 测试 hasPermission 从缓存读取权限
     */
    public function testHasPermissionWithCache(): void
    {
        // 创建一个有 getId 方法的实体
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        // 配置缓存命中情况
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn(true); // 缓存中权限为 true
        
        $this->cache->method('getItem')
            ->with($this->stringContains('row_perm_test-user'))
            ->willReturn($cacheItem);
        
        $result = $this->service->hasPermission($this->user, $entity, PermissionConstantInterface::VIEW);
        
        $this->assertTrue($result);
    }

    /**
     * 测试 hasPermission 检测到 deny 记录时返回 false
     */
    public function testHasPermissionWithDenyRecord(): void
    {
        // 创建一个有 getId 方法的实体
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        // 配置缓存未命中
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with(false);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);
        
        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);

        // 模拟找到 deny 记录
        $denyRecord = $this->createMock(UserRowPermission::class);
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'entityClass' => get_class($entity),
                'entityId' => '123',
                'deny' => true,
                'valid' => true,
            ])
            ->willReturn($denyRecord);
        
        $result = $this->service->hasPermission($this->user, $entity, PermissionConstantInterface::VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * 测试 hasPermission 找到权限记录时返回 true
     */
    public function testHasPermissionWithPermissionRecord(): void
    {
        // 创建一个有 getId 方法的实体
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        // 配置缓存未命中
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        
        $this->cache->method('getItem')->willReturn($cacheItem);

        // 设置 repository 返回不同的结果
        $denyRecordCriteria = [
            'user' => $this->user,
            'entityClass' => get_class($entity),
            'entityId' => '123',
            'deny' => true,
            'valid' => true,
        ];
        
        $permissionRecordCriteria = [
            'user' => $this->user,
            'entityClass' => get_class($entity),
            'entityId' => '123',
            'view' => true,
            'valid' => true,
        ];
        
        $this->repository->method('findOneBy')
            ->willReturnCallback(function($criteria) use ($denyRecordCriteria, $permissionRecordCriteria) {
                if ($criteria == $denyRecordCriteria) {
                    return null; // 没有禁止记录
                } 
                if ($criteria == $permissionRecordCriteria) {
                    return $this->createMock(UserRowPermission::class); // 有权限记录
                }
                return null;
            });
        
        $result = $this->service->hasPermission($this->user, $entity, PermissionConstantInterface::VIEW);
        
        $this->assertTrue($result);
    }

    /**
     * 测试 hasPermission 未找到权限记录时返回 false
     */
    public function testHasPermissionWithoutPermissionRecord(): void
    {
        // 创建一个有 getId 方法的实体
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        // 配置缓存未命中
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(false);
        
        $this->cache->method('getItem')->willReturn($cacheItem);

        // 设置 repository 按顺序返回不同的结果
        $this->repository->method('findOneBy')
            ->willReturn(null); // 始终返回 null
        
        $result = $this->service->hasPermission($this->user, $entity, PermissionConstantInterface::VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * 测试 grantPermission 方法
     */
    public function testGrantPermission(): void
    {
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        $permissions = [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false
        ];
        
        // 模拟已存在的权限记录
        $existingPermission = $this->createMock(UserRowPermission::class);
        
        // 模拟 repository 的 findOneBy 方法返回已存在的权限记录
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'entityClass' => get_class($entity),
                'entityId' => '123',
            ])
            ->willReturn($existingPermission);
        
        // 期望调用 setter 方法来更新权限
        $existingPermission->expects($this->once())
            ->method('setView')
            ->with(true);
        $existingPermission->expects($this->once())
            ->method('setEdit')
            ->with(false);
        $existingPermission->expects($this->once())
            ->method('setValid')
            ->with(true);
        
        // 即使是更新现有记录，代码也会调用 persist
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($existingPermission);
        $this->entityManager->expects($this->once())->method('flush');
        
        $result = $this->service->grantPermission($this->user, $entity, $permissions);
        
        $this->assertSame($existingPermission, $result);
    }

    /**
     * 测试 grantBatchPermissions 方法
     */
    public function testGrantBatchPermissions(): void
    {
        // 创建测试实体
        $entity1 = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        $entity2 = new class {
            public function getId(): string {
                return '456';
            }
        };
        
        $permissions = [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
        ];
        
        // 创建预期的权限对象
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass(get_class($entity1));
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setEdit(false);
        $permission1->setValid(true);
        
        $permission2 = new UserRowPermission();
        $permission2->setEntityClass(get_class($entity2));
        $permission2->setEntityId('456');
        $permission2->setUser($this->user);
        $permission2->setView(true);
        $permission2->setEdit(false);
        $permission2->setValid(true);
        
        // 模拟 repository 的 findOneBy 方法
        $this->repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(null, null);
        
        // 模拟 EntityManager 的 persist 和 flush
        $persistedObjects = [];
        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(UserRowPermission::class))
            ->willReturnCallback(function($permission) use (&$persistedObjects) {
                $persistedObjects[] = $permission;
            });
        $this->entityManager->expects($this->exactly(2))->method('flush');
        
        $result = $this->service->grantBatchPermissions(
            $this->user,
            [$entity1, $entity2],
            $permissions
        );
        
        $this->assertCount(2, $result);
    }

    /**
     * 测试 getQueryConditions 方法
     */
    public function testGetQueryConditions(): void
    {
        $entityClass = 'TestEntity';
        $alias = 'e';
        $permissions = [PermissionConstantInterface::EDIT];
        
        $expectedConditions = [
            ['AND', 'condition1', ['param1' => 'value1']],
            ['OR', 'condition2', ['param2' => 'value2']]
        ];
        
        $this->condManager->expects($this->once())
            ->method('getUserRowWhereStatements')
            ->with($entityClass, $alias, $this->user, $permissions)
            ->willReturn($expectedConditions);
        
        $result = $this->service->getQueryConditions($entityClass, $alias, $this->user, $permissions);
        
        $this->assertSame($expectedConditions, $result);
    }

    /**
     * 测试 getQueryConditions 方法使用默认权限
     */
    public function testGetQueryConditionsWithDefaultPermissions(): void
    {
        $entityClass = 'TestEntity';
        $alias = 'e';
        
        $this->condManager->expects($this->once())
            ->method('getUserRowWhereStatements')
            ->with(
                $entityClass,
                $alias,
                $this->user,
                [PermissionConstantInterface::VIEW] // 默认应使用 VIEW 权限
            );
        
        $this->service->getQueryConditions($entityClass, $alias, $this->user, []);
    }

    /**
     * 测试 grantRowPermission 创建新权限记录
     */
    public function testGrantRowPermissionCreatesNewRecord(): void
    {
        // 创建一个有 getId 方法的实体
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        $request = new GrantRowPermissionRequest();
        $request->setUser($this->user);
        $request->setObject($entity);
        $request->setView(true);
        
        // 模拟查找记录
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'user' => $this->user,
                'entityClass' => get_class($entity),
                'entityId' => '123',
            ])
            ->willReturn(null);
        
        // 检查 persist 方法是否按照预期调用
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function(UserRowPermission $permission) use ($entity) {
                $isValid = 
                    $permission->getUser() === $this->user &&
                    $permission->getEntityClass() === get_class($entity) &&
                    $permission->getEntityId() === '123' &&
                    $permission->isView() === true;
                return $isValid;
            }));
        
        // 确保调用了 flush 方法
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->service->grantRowPermission($request);
        
        // 验证返回值类型
        $this->assertInstanceOf(UserRowPermission::class, $result);
    }
} 