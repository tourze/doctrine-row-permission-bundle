<?php

namespace Tourze\DoctrineRowPermissionBundle\Interface;

/**
 * 权限常量接口
 */
interface PermissionConstantInterface
{
    /**
     * 查看权限
     */
    public const VIEW = 'view';

    /**
     * 编辑权限
     */
    public const EDIT = 'edit';

    /**
     * 删除/解除关联权限
     */
    public const UNLINK = 'unlink';

    /**
     * 拒绝访问权限（优先级最高）
     */
    public const DENY = 'deny';
}
