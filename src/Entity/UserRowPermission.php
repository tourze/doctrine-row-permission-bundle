<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 这个希望做的是对RBAC体系进行权限控制上的补充，做到行级数据的控制。
 * 行级别的数据权限模型（Row-Level Security，RLS）：该模型通过在数据表中添加行级别的权限控制规则，来限制用户对数据的访问。例如，可以为每个用户或角色分配一个过滤器，该过滤器只允许其访问表中特定的行。
 * 参考 https://github.com/symfony/acl-bundle/blob/main/src/Resources/doc/index.rst 使用mask来做权限控制
 */
#[ORM\Entity(repositoryClass: UserRowPermissionRepository::class)]
#[ORM\Table(name: 'ims_entity_permission', options: ['comment' => '用户行级数据权限'])]
#[ORM\UniqueConstraint(name: 'ims_entity_permission_idx_uniq', columns: ['entity_class', 'entity_id', 'user_id'])]
class UserRowPermission implements PermissionConstantInterface, \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    public function __construct()
    {
        // 初始化必填属性为空字符串,后续通过 setter 设置实际值
        $this->entityClass = '';
        $this->entityId = '';
    }

    public const VIEW = 'view';

    public const EDIT = 'edit';

    public const UNLINK = 'unlink';

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(length: 255, options: ['comment' => '实体类名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $entityClass;

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(length: 64, options: ['comment' => '实体ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $entityId;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserInterface $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '禁止访问', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $deny = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '允许查看', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $view = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '允许编辑', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $edit = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '允许解除关联', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $unlink = false;

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['comment' => '有效', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $valid = false;

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function isDeny(): bool
    {
        return $this->deny;
    }

    public function setDeny(bool $deny): void
    {
        $this->deny = $deny;
    }

    public function isView(): bool
    {
        return $this->view;
    }

    public function setView(bool $view): void
    {
        $this->view = $view;
    }

    public function isEdit(): bool
    {
        return $this->edit;
    }

    public function setEdit(bool $edit): void
    {
        $this->edit = $edit;
    }

    public function isUnlink(): bool
    {
        return $this->unlink;
    }

    public function setUnlink(bool $unlink): void
    {
        $this->unlink = $unlink;
    }

    public function __toString(): string
    {
        return sprintf('UserRowPermission[%s:%s:%s]', $this->entityClass, $this->entityId, $this->user?->getUserIdentifier() ?? 'anonymous');
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
