<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Service\CondManager;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;

class SecurityServiceCacheTest extends TestCase
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
     * 测试 cacheResult 方法正常缓存结果
     */
    public function testCacheResultSavesResult(): void
    {
        $cacheKey = 'test_cache_key';
        $result = true;
        
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('set')->with($result);
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600);
        
        $this->cache->expects($this->once())->method('getItem')->with($cacheKey)->willReturn($cacheItem);
        $this->cache->expects($this->once())->method('save')->with($cacheItem);
        
        // 使用反射来调用私有方法
        $reflectedService = new \ReflectionClass(SecurityService::class);
        $method = $reflectedService->getMethod('cacheResult');
        $method->setAccessible(true);
        $method->invoke($this->service, $cacheKey, $result);
    }
    
    /**
     * 测试 cacheResult 方法处理异常
     */
    public function testCacheResultHandlesException(): void
    {
        $cacheKey = 'test_cache_key';
        $result = true;
        
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->method('set')->willThrowException(new \Exception('Cache error'));
        
        $this->cache->method('getItem')->willReturn($cacheItem);
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('缓存权限结果失败', $this->arrayHasKey('error'));
        
        // 使用反射来调用私有方法
        $reflectedService = new \ReflectionClass(SecurityService::class);
        $method = $reflectedService->getMethod('cacheResult');
        $method->setAccessible(true);
        $method->invoke($this->service, $cacheKey, $result);
    }
    
    /**
     * 测试 clearPermissionCache 方法正常清除缓存
     */
    public function testClearPermissionCacheDeletesItems(): void
    {
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        // 期望删除所有权限类型的缓存项
        $this->cache->expects($this->once())
            ->method('deleteItems')
            ->with($this->callback(function ($cacheKeys) {
                return count($cacheKeys) === 3 // 三种权限类型
                    && strpos($cacheKeys[0], PermissionConstantInterface::VIEW) !== false
                    && strpos($cacheKeys[1], PermissionConstantInterface::EDIT) !== false
                    && strpos($cacheKeys[2], PermissionConstantInterface::UNLINK) !== false;
            }));
        
        // 使用反射来调用私有方法
        $reflectedService = new \ReflectionClass(SecurityService::class);
        $method = $reflectedService->getMethod('clearPermissionCache');
        $method->setAccessible(true);
        $method->invoke($this->service, $this->user, $entity);
    }
    
    /**
     * 测试 clearPermissionCache 方法处理异常
     */
    public function testClearPermissionCacheHandlesException(): void
    {
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        $this->cache->method('deleteItems')->willThrowException(new \Exception('Cache error'));
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('清除权限缓存失败', $this->arrayHasKey('error'));
        
        // 使用反射来调用私有方法
        $reflectedService = new \ReflectionClass(SecurityService::class);
        $method = $reflectedService->getMethod('clearPermissionCache');
        $method->setAccessible(true);
        $method->invoke($this->service, $this->user, $entity);
    }
    
    /**
     * 测试没有缓存实例时 cacheResult 不做任何操作
     */
    public function testCacheResultWithNullCache(): void
    {
        // 创建一个没有缓存的服务实例
        $serviceWithoutCache = new SecurityService(
            $this->repository,
            $this->entityManager,
            $this->logger,
            $this->condManager,
            null // 不提供缓存
        );
        
        // 使用反射来调用私有方法
        $reflectedService = new \ReflectionClass(SecurityService::class);
        $method = $reflectedService->getMethod('cacheResult');
        $method->setAccessible(true);
        
        // 方法应该早期返回，没有异常
        $method->invoke($serviceWithoutCache, 'test_key', true);
        $this->addToAssertionCount(1); // 验证没有异常
    }
    
    /**
     * 测试没有缓存实例时 clearPermissionCache 不做任何操作
     */
    public function testClearPermissionCacheWithNullCache(): void
    {
        $entity = new class {
            public function getId(): string {
                return '123';
            }
        };
        
        // 创建一个没有缓存的服务实例
        $serviceWithoutCache = new SecurityService(
            $this->repository,
            $this->entityManager,
            $this->logger,
            $this->condManager,
            null // 不提供缓存
        );
        
        // 使用反射来调用私有方法
        $reflectedService = new \ReflectionClass(SecurityService::class);
        $method = $reflectedService->getMethod('clearPermissionCache');
        $method->setAccessible(true);
        
        // 方法应该早期返回，没有异常
        $method->invoke($serviceWithoutCache, $this->user, $entity);
        $this->addToAssertionCount(1); // 验证没有异常
    }
} 