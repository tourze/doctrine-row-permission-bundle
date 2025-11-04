<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 用户行级权限仓库
 *
 * @extends ServiceEntityRepository<UserRowPermission>
 */
#[AsRepository(entityClass: UserRowPermission::class)]
class UserRowPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRowPermission::class);
    }

    /**
     * 查找用户对特定实体的权限记录
     *
     * @param UserInterface $user        用户
     * @param string        $entityClass 实体类名
     * @param string        $entityId    实体ID
     *
     * @return UserRowPermission|null 权限记录
     */
    public function findPermission(UserInterface $user, string $entityClass, string $entityId): ?UserRowPermission
    {
        return $this->findOneBy([
            'user' => $user,
            'entityClass' => $entityClass,
            'entityId' => $entityId,
            'valid' => true,
        ]);
    }

    /**
     * 查找具有特定权限的记录
     *
     * @param UserInterface $user        用户
     * @param string        $entityClass 实体类名
     * @param string        $entityId    实体ID
     * @param string        $permission  权限类型
     *
     * @return UserRowPermission|null 权限记录
     */
    public function findByPermission(
        UserInterface $user,
        string $entityClass,
        string $entityId,
        string $permission,
    ): ?UserRowPermission {
        if (!in_array($permission, [
            PermissionConstantInterface::VIEW,
            PermissionConstantInterface::EDIT,
            PermissionConstantInterface::UNLINK,
            PermissionConstantInterface::DENY,
        ], true)) {
            return null;
        }

        return $this->findOneBy([
            'user' => $user,
            'entityClass' => $entityClass,
            'entityId' => $entityId,
            $permission => true,
            'valid' => true,
        ]);
    }

    /**
     * 批量查找指定用户对多个实体的权限
     *
     * @param UserInterface $user        用户
     * @param string        $entityClass 实体类名
     * @param array<string> $entityIds   实体ID数组
     *
     * @return UserRowPermission[] 权限记录数组
     */
    public function findBatchPermissions(UserInterface $user, string $entityClass, array $entityIds): array
    {
        /** @var UserRowPermission[] $result */
        $result = $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.entityClass = :entityClass')
            ->andWhere('p.entityId IN (:entityIds)')
            ->andWhere('p.valid = :valid')
            ->setParameter('user', $user)
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityIds', $entityIds)
            ->setParameter('valid', true)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * 保存权限记录
     *
     * @param UserRowPermission $entity 权限实体
     * @param bool $flush 是否立即刷新到数据库
     */
    public function save(UserRowPermission $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除权限记录
     *
     * @param UserRowPermission $entity 权限实体
     * @param bool $flush 是否立即刷新到数据库
     */
    public function remove(UserRowPermission $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
