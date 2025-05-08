<?php

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
    private UserInterface $user;

    /**
     * 实体对象
     */
    private object $object;

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

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getObject(): object
    {
        return $this->object;
    }

    public function setObject(object $object): self
    {
        $this->object = $object;
        return $this;
    }

    public function getView(): ?bool
    {
        return $this->view;
    }

    public function setView(?bool $view): self
    {
        $this->view = $view;
        return $this;
    }

    public function getEdit(): ?bool
    {
        return $this->edit;
    }

    public function setEdit(?bool $edit): self
    {
        $this->edit = $edit;
        return $this;
    }

    public function getUnlink(): ?bool
    {
        return $this->unlink;
    }

    public function setUnlink(?bool $unlink): self
    {
        $this->unlink = $unlink;
        return $this;
    }

    public function getDeny(): ?bool
    {
        return $this->deny;
    }

    public function setDeny(?bool $deny): self
    {
        $this->deny = $deny;
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;
        return $this;
    }

    /**
     * 从权限数组创建请求
     */
    public static function fromArray(UserInterface $user, object $entity, array $permissions): self
    {
        $request = new self();
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

        if (isset($permissions['remark'])) {
            $request->setRemark($permissions['remark']);
        }

        return $request;
    }
}
