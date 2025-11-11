<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Exception\InvalidEntityException;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Request\GrantRowPermissionRequest;

/**
 * 行级权限安全服务
 */
#[WithMonologChannel(channel: 'doctrine_row_permission')]
#[Autoconfigure(public: true)]
readonly class SecurityService implements RowPermissionInterface
{
    /**
     * @param UserRowPermissionRepository $rowPermissionRepository 权限仓库
     * @param EntityManagerInterface      $entityManager           实体管理器
     * @param LoggerInterface             $logger                  日志接口
     * @param CondManager                 $condManager             条件管理器
     * @param CacheItemPoolInterface|null $cache                   缓存接口
     */
    public function __construct(
        private UserRowPermissionRepository $rowPermissionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private CondManager $condManager,
        private ?CacheItemPoolInterface $cache = null,
    ) {
    }

    /**
     * 检查用户是否有权限访问指定实体
     *
     * @param UserInterface|null $user       用户对象
     * @param object             $entity     实体对象
     * @param string             $permission 权限类型
     *
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
            $entityId = $entity->getId();
            if (null === $entityId) {
                return false;
            }

            // 确保 entityId 可以转换为字符串
            assert(is_scalar($entityId) || (is_object($entityId) && method_exists($entityId, '__toString')), 'Entity ID must be convertible to string');
            $entityIdString = (string) $entityId;

            // 尝试从缓存获取
            $realClass = $this->getRealClassName($entity);
            $cacheKey = sprintf(
                'row_perm_%s_%s_%s_%s',
                $user->getUserIdentifier(),
                $realClass,
                $entityIdString,
                $permission
            );

            if (null !== $this->cache) {
                $cacheItem = $this->cache->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    /** @var bool $cachedValue */
                    $cachedValue = $cacheItem->get();

                    return $cachedValue;
                }
            }

            // 检查是否有 deny 标记的记录
            $denyRecord = $this->rowPermissionRepository->findOneBy([
                'user' => $user,
                'entityClass' => $realClass,
                'entityId' => $entityIdString,
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
                'entityClass' => $realClass,
                'entityId' => $entityIdString,
                $permission => true,
                'valid' => true,
            ]);

            $result = (null !== $permRecord);
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
     * 获取实体的真实类名
     */
    private function getRealClassName(object $entity): string
    {
        $class = get_class($entity);

        return ClassUtils::getRealClass($class);
    }

    /**
     * 为用户授予实体权限
     *
     * @param UserInterface $user        用户对象
     * @param object        $entity      实体对象
     * @param array<string, bool> $permissions 权限配置
     *
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
     * @param UserInterface $user        用户对象
     * @param array<object> $entities    实体对象数组
     * @param array<string, bool> $permissions 权限配置
     *
     * @return array<object> 权限记录对象数组
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
     * @param string             $entityClass 实体类名
     * @param string             $alias       实体别名
     * @param UserInterface|null $user        用户对象
     * @param array<string>      $permissions 权限类型列表
     *
     * @return array<array{string, string, array<string, mixed>}> 查询条件数组
     */
    public function getQueryConditions(string $entityClass, string $alias, ?UserInterface $user, array $permissions = []): array
    {
        if ([] === $permissions) {
            $permissions = [PermissionConstantInterface::VIEW];
        }

        return $this->condManager->getUserRowWhereStatements($entityClass, $alias, $user, $permissions);
    }

    /**
     * 为指定用户分配指定对象的权限
     */
    public function grantRowPermission(GrantRowPermissionRequest $request): UserRowPermission
    {
        [$user, $object, $entityIdString, $className] = $this->validateAndExtractRequestData($request);

        $def = $this->findOrCreatePermission($user, $className, $entityIdString);
        $this->applyPermissionsFromRequest($request, $def);
        $this->persistPermission($def, $user, $object, $className, $entityIdString);

        return $def;
    }

    /**
     * 验证请求并提取数据
     *
     * @return array{UserInterface, object, string, string}
     */
    private function validateAndExtractRequestData(GrantRowPermissionRequest $request): array
    {
        $user = $request->getUser();
        if (null === $user) {
            throw new InvalidEntityException('用户不能为空');
        }

        $object = $request->getObject();
        if (null === $object) {
            throw new InvalidEntityException('实体对象不能为空');
        }

        if (!method_exists($object, 'getId')) {
            throw new InvalidEntityException('实体必须有 getId 方法');
        }

        $entityId = $object->getId();
        if (null === $entityId) {
            throw new InvalidEntityException('实体 ID 不能为空');
        }

        assert(is_scalar($entityId) || (is_object($entityId) && method_exists($entityId, '__toString')), 'Entity ID must be convertible to string');
        $entityIdString = (string) $entityId;
        $className = $this->getRealClassName($object);

        return [$user, $object, $entityIdString, $className];
    }

    /**
     * 查找或创建权限记录
     */
    private function findOrCreatePermission(UserInterface $user, string $className, string $entityIdString): UserRowPermission
    {
        $def = $this->rowPermissionRepository->findOneBy([
            'user' => $user,
            'entityClass' => $className,
            'entityId' => $entityIdString,
        ]);

        if (null === $def) {
            $def = new UserRowPermission();
            $def->setUser($user);
            $def->setEntityClass($className);
            $def->setEntityId($entityIdString);
        }

        return $def;
    }

    /**
     * 从请求应用权限到实体
     */
    private function applyPermissionsFromRequest(GrantRowPermissionRequest $request, UserRowPermission $def): void
    {
        if (null !== $request->getDeny()) {
            $def->setDeny($request->getDeny());
        }

        if (null !== $request->getView()) {
            $def->setView($request->getView());
        }

        if (null !== $request->getEdit()) {
            $def->setEdit($request->getEdit());
        }

        if (null !== $request->getUnlink()) {
            $def->setUnlink($request->getUnlink());
        }

        $def->setValid(true);
    }

    /**
     * 持久化权限记录
     */
    private function persistPermission(
        UserRowPermission $def,
        UserInterface $user,
        object $object,
        string $className,
        string $entityIdString
    ): void {
        try {
            $this->entityManager->persist($def);
            $this->entityManager->flush();
            $this->clearPermissionCache($user, $object);
        } catch (\Throwable $e) {
            $this->logger->error('保存权限记录失败', [
                'error' => $e->getMessage(),
                'entity_class' => $className,
                'entity_id' => $entityIdString,
                'exception' => $e,
            ]);
            throw $e;
        }
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
                PermissionConstantInterface::UNLINK,
            ] as $permission) {
                $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;
                if (null !== $entityId) {
                    assert(is_scalar($entityId) || (is_object($entityId) && method_exists($entityId, '__toString')), 'Entity ID must be convertible to string');
                    $entityIdString = (string) $entityId;
                    $realEntityClass = $this->getRealClassName($entity);
                    $cacheKeys[] = sprintf(
                        'row_perm_%s_%s_%s_%s',
                        $user->getUserIdentifier(),
                        $realEntityClass,
                        $entityIdString,
                        $permission
                    );
                }
            }

            $this->cache->deleteItems($cacheKeys);
        } catch (\Throwable $e) {
            $this->logger->warning('清除权限缓存失败', [
                'error' => $e->getMessage(),
                'entity_class' => get_class($entity),
                'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : 'unknown',
            ]);
        }
    }
}
