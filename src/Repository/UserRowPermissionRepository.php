<?php

namespace Tourze\DoctrineRowPermissionBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

/**
 * 用户行级权限仓库
 *
 * @method UserRowPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRowPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRowPermission[] findAll()
 * @method UserRowPermission[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRowPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRowPermission::class);
    }
    
    /**
     * 查找用户对特定实体的权限记录
     *
     * @param UserInterface $user 用户
     * @param string $entityClass 实体类名
     * @param string $entityId 实体ID
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
     * @param UserInterface $user 用户
     * @param string $entityClass 实体类名
     * @param string $entityId 实体ID
     * @param string $permission 权限类型
     * @return UserRowPermission|null 权限记录
     */
    public function findByPermission(
        UserInterface $user, 
        string $entityClass, 
        string $entityId, 
        string $permission
    ): ?UserRowPermission {
        if (!in_array($permission, [
            PermissionConstantInterface::VIEW,
            PermissionConstantInterface::EDIT,
            PermissionConstantInterface::UNLINK,
            PermissionConstantInterface::DENY,
        ])) {
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
     * @param UserInterface $user 用户
     * @param string $entityClass 实体类名
     * @param array $entityIds 实体ID数组
     * @return UserRowPermission[] 权限记录数组
     */
    public function findBatchPermissions(UserInterface $user, string $entityClass, array $entityIds): array
    {
        return $this->createQueryBuilder('p')
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
    }
}
