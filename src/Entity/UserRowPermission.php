<?php

namespace Tourze\DoctrineRowPermissionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

/**
 * 这个希望做的是对RBAC体系进行权限控制上的补充，做到行级数据的控制。
 * 行级别的数据权限模型（Row-Level Security，RLS）：该模型通过在数据表中添加行级别的权限控制规则，来限制用户对数据的访问。例如，可以为每个用户或角色分配一个过滤器，该过滤器只允许其访问表中特定的行。
 * 参考 https://github.com/symfony/acl-bundle/blob/main/src/Resources/doc/index.rst 使用mask来做权限控制
 */
#[AsPermission(title: '用户行级数据权限')]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: UserRowPermissionRepository::class)]
#[ORM\Table(name: 'ims_entity_permission', options: ['comment' => '用户行级数据权限'])]
#[ORM\UniqueConstraint(name: 'ims_entity_permission_idx_uniq', columns: ['entity_class', 'entity_id', 'user_id'])]
#[ORM\Index(name: 'ims_entity_permission_entity_class_idx', columns: ['entity_class'])]
#[ORM\Index(name: 'ims_entity_permission_entity_id_idx', columns: ['entity_id'])]
#[ORM\Index(name: 'ims_entity_permission_user_id_idx', columns: ['user_id'])]
class UserRowPermission implements PermissionConstantInterface
{
    public const VIEW = 'view';

    public const EDIT = 'edit';

    public const UNLINK = 'unlink';

    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ListColumn]
    #[FormField]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(length: 255, options: ['comment' => '实体类名'])]
    private string $entityClass;

    #[ListColumn]
    #[FormField]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(length: 64, options: ['comment' => '实体ID'])]
    private string $entityId;

    #[ListColumn]
    #[FormField]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserInterface $user = null;

    #[FormField]
    #[ListColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '禁止访问', 'default' => false])]
    private bool $deny = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '允许查看', 'default' => false])]
    private bool $view = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '允许编辑', 'default' => false])]
    private bool $edit = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '允许解除关联', 'default' => false])]
    private bool $unlink = false;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '有效', 'default' => false])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private bool $valid = false;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

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

    public function isDeny(): bool
    {
        return $this->deny;
    }

    public function setDeny(bool $deny): self
    {
        $this->deny = $deny;

        return $this;
    }

    public function isView(): bool
    {
        return $this->view;
    }

    public function setView(bool $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function isEdit(): bool
    {
        return $this->edit;
    }

    public function setEdit(bool $edit): self
    {
        $this->edit = $edit;

        return $this;
    }

    public function isUnlink(): bool
    {
        return $this->unlink;
    }

    public function setUnlink(bool $unlink): self
    {
        $this->unlink = $unlink;

        return $this;
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    /**
     * 检查当前权限记录是否允许特定操作
     */
    public function hasPermission(string $permissionType): bool
    {
        if ($this->deny) {
            return false;
        }

        if (!$this->valid) {
            return false;
        }

        return match ($permissionType) {
            self::VIEW => $this->view,
            self::EDIT => $this->edit,
            self::UNLINK => $this->unlink,
            default => false,
        };
    }
}
