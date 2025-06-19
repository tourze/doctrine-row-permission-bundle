<?php

namespace Tourze\DoctrineRowPermissionBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Request\GrantRowPermissionRequest;

/**
 * 行级权限安全服务
 */
class SecurityService implements RowPermissionInterface
{
    /**
     * @param UserRowPermissionRepository $rowPermissionRepository 权限仓库
     * @param EntityManagerInterface $entityManager 实体管理器
     * @param LoggerInterface $logger 日志接口
     * @param CondManager $condManager 条件管理器
     * @param CacheItemPoolInterface|null $cache 缓存接口
     */
    public function __construct(
        private readonly UserRowPermissionRepository $rowPermissionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly CondManager $condManager,
        private readonly ?CacheItemPoolInterface $cache = null,
    ) {
    }

    /**
     * 检查用户是否有权限访问指定实体
     * 
     * @param UserInterface|null $user 用户对象
     * @param object $entity 实体对象
     * @param string $permission 权限类型
     * @return bool 是否有权限
     */
    public function hasPermission(?UserInterface $user, object $entity, string $permission): bool
    {
        if (null === $user) {
            return false;
        }
        
        if (!method_exists($entity, 'getId')) {
            $this->logger->warning('实体没有 getId 方法，无法检查权限', [
                'entity_class' => get_class($entity),
            ]);
            return false;
        }
        
        try {
            // 尝试从缓存获取
            $cacheKey = sprintf(
                'row_perm_%s_%s_%s_%s',
                $user->getUserIdentifier(),
                ClassUtils::getRealClass(get_class($entity)),
                $entity->getId(),
                $permission
            );
            
            if (null !== $this->cache) {
                $cacheItem = $this->cache->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
                }
            }
            
            // 检查是否有 deny 标记的记录
            $denyRecord = $this->rowPermissionRepository->findOneBy([
                'user' => $user,
                'entityClass' => ClassUtils::getRealClass(get_class($entity)),
                'entityId' => $entity->getId(),
                'deny' => true,
                'valid' => true,
            ]);
            
            if (null !== $denyRecord) {
                $this->cacheResult($cacheKey, false);
                return false;
            }
            
            // 检查是否有指定权限的记录
            $permRecord = $this->rowPermissionRepository->findOneBy([
                'user' => $user,
                'entityClass' => ClassUtils::getRealClass(get_class($entity)),
                'entityId' => $entity->getId(),
                $permission => true,
                'valid' => true,
            ]);
            
            $result = ($permRecord !== null);
            $this->cacheResult($cacheKey, $result);
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('检查权限时出错', [
                'error' => $e->getMessage(),
                'entity_class' => get_class($entity),
                'entity_id' => $entity->getId(),
                'permission' => $permission,
                'exception' => $e,
            ]);
            return false;
        }
    }
    
    /**
     * 缓存权限检查结果
     */
    private function cacheResult(string $key, bool $result): void
    {
        if (null === $this->cache) {
            return;
        }
        
        try {
            $cacheItem = $this->cache->getItem($key);
            $cacheItem->set($result);
            $cacheItem->expiresAfter(3600); // 缓存1小时
            $this->cache->save($cacheItem);
        } catch (\Throwable $e) {
            $this->logger->warning('缓存权限结果失败', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 为用户授予实体权限
     * 
     * @param UserInterface $user 用户对象
     * @param object $entity 实体对象
     * @param array $permissions 权限配置
     * @return UserRowPermission 权限记录对象
     */
    public function grantPermission(UserInterface $user, object $entity, array $permissions): object
    {
        $request = new GrantRowPermissionRequest();
        $request->setUser($user);
        $request->setObject($entity);
        
        if (isset($permissions[PermissionConstantInterface::VIEW])) {
            $request->setView($permissions[PermissionConstantInterface::VIEW]);
        }
        
        if (isset($permissions[PermissionConstantInterface::EDIT])) {
            $request->setEdit($permissions[PermissionConstantInterface::EDIT]);
        }
        
        if (isset($permissions[PermissionConstantInterface::UNLINK])) {
            $request->setUnlink($permissions[PermissionConstantInterface::UNLINK]);
        }
        
        if (isset($permissions[PermissionConstantInterface::DENY])) {
            $request->setDeny($permissions[PermissionConstantInterface::DENY]);
        }
        
        return $this->grantRowPermission($request);
    }
    
    /**
     * 批量为用户授予实体权限
     * 
     * @param UserInterface $user 用户对象 
     * @param array $entities 实体对象数组
     * @param array $permissions 权限配置
     * @return array 权限记录对象数组
     */
    public function grantBatchPermissions(UserInterface $user, array $entities, array $permissions): array
    {
        $results = [];
        
        foreach ($entities as $entity) {
            $results[] = $this->grantPermission($user, $entity, $permissions);
        }
        
        return $results;
    }
    
    /**
     * 为指定实体生成查询条件
     * 
     * @param string $entityClass 实体类名
     * @param string $alias 实体别名
     * @param UserInterface|null $user 用户对象
     * @param array $permissions 权限类型列表
     * @return array 查询条件数组
     */
    public function getQueryConditions(string $entityClass, string $alias, ?UserInterface $user, array $permissions = []): array
    {
        if (empty($permissions)) {
            $permissions = [PermissionConstantInterface::VIEW];
        }
        
        return $this->condManager->getUserRowWhereStatements($entityClass, $alias, $user, $permissions);
    }

    /**
     * 为指定用户分配指定对象的权限
     */
    public function grantRowPermission(GrantRowPermissionRequest $request): UserRowPermission
    {
        $user = $request->getUser();
        $object = $request->getObject();

        $className = ClassUtils::getRealClass(get_class($object));
        $def = $this->rowPermissionRepository->findOneBy([
            'user' => $user,
            'entityClass' => $className,
            'entityId' => $object->getId(),
        ]);
        
        if (null === $def) {
            $def = new UserRowPermission();
            $def->setUser($user);
            $def->setEntityClass($className);
            $def->setEntityId($object->getId());
        }

        // 设置权限
        if ($request->getDeny() !== null) {
            $def->setDeny((bool) $request->getDeny());
        }
        
        if ($request->getView() !== null) {
            $def->setView((bool) $request->getView());
        }
        
        if ($request->getEdit() !== null) {
            $def->setEdit((bool) $request->getEdit());
        }
        
        if ($request->getUnlink() !== null) {
            $def->setUnlink((bool) $request->getUnlink());
        }
        
        $def->setValid(true);

        // 保存权限
        try {
            $this->entityManager->persist($def);
            $this->entityManager->flush();
            
            // 清除缓存
            $this->clearPermissionCache($user, $object);
            
        } catch (\Throwable $e) {
            $this->logger->error('保存权限记录失败', [
                'error' => $e->getMessage(),
                'entity_class' => $className,
                'entity_id' => $object->getId(),
                'exception' => $e,
            ]);
            throw $e;
        }

        return $def;
    }
    
    /**
     * 清除实体权限缓存
     */
    private function clearPermissionCache(UserInterface $user, object $entity): void
    {
        if (null === $this->cache) {
            return;
        }
        
        try {
            $cacheKeys = [];
            foreach ([
                PermissionConstantInterface::VIEW,
                PermissionConstantInterface::EDIT,
                PermissionConstantInterface::UNLINK
            ] as $permission) {
                $cacheKeys[] = sprintf(
                    'row_perm_%s_%s_%s_%s',
                    $user->getUserIdentifier(),
                    ClassUtils::getRealClass(get_class($entity)),
                    $entity->getId(),
                    $permission
                );
            }
            
            $this->cache->deleteItems($cacheKeys);
        } catch (\Throwable $e) {
            $this->logger->warning('清除权限缓存失败', [
                'error' => $e->getMessage(),
                'entity_class' => get_class($entity),
                'entity_id' => $entity->getId(),
            ]);
        }
    }
}
