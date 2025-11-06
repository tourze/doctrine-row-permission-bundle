<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Request;

use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

/**
 * 授予行级权限请求
 */
class GrantRowPermissionRequest
{
    /**
     * 用户
     */
    private ?UserInterface $user = null;

    /**
     * 实体对象
     */
    private ?object $object = null;

    /**
     * 查看权限
     */
    private ?bool $view = null;

    /**
     * 编辑权限
     */
    private ?bool $edit = null;

    /**
     * 解除关联权限
     */
    private ?bool $unlink = null;

    /**
     * 拒绝访问权限
     */
    private ?bool $deny = null;

    /**
     * 备注
     */
    private ?string $remark = null;

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getObject(): ?object
    {
        return $this->object;
    }

    public function setObject(object $object): void
    {
        $this->object = $object;
    }

    public function getView(): ?bool
    {
        return $this->view;
    }

    public function setView(?bool $view): void
    {
        $this->view = $view;
    }

    public function getEdit(): ?bool
    {
        return $this->edit;
    }

    public function setEdit(?bool $edit): void
    {
        $this->edit = $edit;
    }

    public function getUnlink(): ?bool
    {
        return $this->unlink;
    }

    public function setUnlink(?bool $unlink): void
    {
        $this->unlink = $unlink;
    }

    public function getDeny(): ?bool
    {
        return $this->deny;
    }

    public function setDeny(?bool $deny): void
    {
        $this->deny = $deny;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    /**
     * 从权限数组创建请求
     *
     * @param array<string, mixed> $permissions
     */
    public static function fromArray(UserInterface $user, object $entity, array $permissions): self
    {
        $request = new self();
        $request->setUser($user);
        $request->setObject($entity);

        $request->applyBooleanPermission($permissions, PermissionConstantInterface::VIEW, $request->setView(...));
        $request->applyBooleanPermission($permissions, PermissionConstantInterface::EDIT, $request->setEdit(...));
        $request->applyBooleanPermission($permissions, PermissionConstantInterface::UNLINK, $request->setUnlink(...));
        $request->applyBooleanPermission($permissions, PermissionConstantInterface::DENY, $request->setDeny(...));
        $request->applyStringField($permissions, 'remark', $request->setRemark(...));

        return $request;
    }

    /**
     * 应用布尔类型权限
     *
     * @param array<string, mixed> $permissions
     * @param callable(?bool): void $setter
     */
    private function applyBooleanPermission(array $permissions, string $key, callable $setter): void
    {
        if (isset($permissions[$key])) {
            $value = $permissions[$key];
            $setter(is_bool($value) ? $value : null);
        }
    }

    /**
     * 应用字符串类型字段
     *
     * @param array<string, mixed> $permissions
     * @param callable(?string): void $setter
     */
    private function applyStringField(array $permissions, string $key, callable $setter): void
    {
        if (isset($permissions[$key])) {
            $value = $permissions[$key];
            $setter(is_string($value) ? $value : null);
        }
    }
}
