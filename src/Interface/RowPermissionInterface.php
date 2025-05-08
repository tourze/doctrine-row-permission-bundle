<?php

namespace Tourze\DoctrineRowPermissionBundle\Interface;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * 行级权限接口
 */
interface RowPermissionInterface
{
    /**
     * 检查用户是否有权限访问指定实体
     *
     * @param UserInterface|null $user 用户对象
     * @param object $entity 实体对象
     * @param string $permission 权限类型
     * @return bool 是否有权限
     */
    public function hasPermission(?UserInterface $user, object $entity, string $permission): bool;

    /**
     * 为用户授予实体权限
     *
     * @param UserInterface $user 用户对象
     * @param object $entity 实体对象
     * @param array $permissions 权限配置
     * @return object 权限记录对象
     */
    public function grantPermission(UserInterface $user, object $entity, array $permissions): object;

    /**
     * 批量为用户授予实体权限
     *
     * @param UserInterface $user 用户对象
     * @param array $entities 实体对象数组
     * @param array $permissions 权限配置
     * @return array 权限记录对象数组
     */
    public function grantBatchPermissions(UserInterface $user, array $entities, array $permissions): array;

    /**
     * 为指定实体生成查询条件
     *
     * @param string $entityClass 实体类名
     * @param string $alias 实体别名
     * @param UserInterface|null $user 用户对象
     * @param array $permissions 权限类型列表
     * @return array 查询条件数组
     */
    public function getQueryConditions(string $entityClass, string $alias, ?UserInterface $user, array $permissions = []): array;
}
