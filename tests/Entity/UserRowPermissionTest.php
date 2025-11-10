<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(UserRowPermission::class)]
final class UserRowPermissionTest extends AbstractEntityTestCase
{
    private UserRowPermission $userRowPermission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRowPermission = new UserRowPermission();
    }

    /**
     * 测试实体是否实现了权限常量接口
     */
    public function testImplementsPermissionConstantInterface(): void
    {
        $this->assertInstanceOf(PermissionConstantInterface::class, $this->userRowPermission);
    }

    /**
     * 测试 ID getter/setter
     */
    public function testGetId(): void
    {
        // ID 默认值测试
        $this->assertEquals(null, $this->userRowPermission->getId());
    }

    /**
     * 测试实体类名 getter/setter
     */
    public function testEntityClassGetterSetter(): void
    {
        $className = 'TestEntityClass';

        $this->userRowPermission->setEntityClass($className);
        $this->assertEquals($className, $this->userRowPermission->getEntityClass());
    }

    /**
     * 测试实体ID getter/setter
     */
    public function testEntityIdGetterSetter(): void
    {
        $entityId = '12345';

        $this->userRowPermission->setEntityId($entityId);
        $this->assertEquals($entityId, $this->userRowPermission->getEntityId());
    }

    /**
     * 测试用户 getter/setter
     */
    public function testUserGetterSetter(): void
    {
        $user = $this->createMock(UserInterface::class);

        $this->userRowPermission->setUser($user);
        $this->assertSame($user, $this->userRowPermission->getUser());
    }

    /**
     * 测试备注 getter/setter
     */
    public function testRemarkGetterSetter(): void
    {
        $remark = 'Test remark';

        $this->userRowPermission->setRemark($remark);
        $this->assertEquals($remark, $this->userRowPermission->getRemark());

        // 测试 null 值
        $this->userRowPermission->setRemark(null);
        $this->assertNull($this->userRowPermission->getRemark());
    }

    /**
     * 测试 deny 属性 getter/setter
     */
    public function testDenyGetterSetter(): void
    {
        // 默认值应为 false
        $this->assertFalse($this->userRowPermission->isDeny());

        // 设置为 true
        $this->userRowPermission->setDeny(true);
        $this->assertTrue($this->userRowPermission->isDeny());

        // 设置回 false
        $this->userRowPermission->setDeny(false);
        $this->assertFalse($this->userRowPermission->isDeny());
    }

    /**
     * 测试 view 属性 getter/setter
     */
    public function testViewGetterSetter(): void
    {
        // 默认值应为 false
        $this->assertFalse($this->userRowPermission->isView());

        // 设置为 true
        $this->userRowPermission->setView(true);
        $this->assertTrue($this->userRowPermission->isView());

        // 设置回 false
        $this->userRowPermission->setView(false);
        $this->assertFalse($this->userRowPermission->isView());
    }

    /**
     * 测试 edit 属性 getter/setter
     */
    public function testEditGetterSetter(): void
    {
        // 默认值应为 false
        $this->assertFalse($this->userRowPermission->isEdit());

        // 设置为 true
        $this->userRowPermission->setEdit(true);
        $this->assertTrue($this->userRowPermission->isEdit());

        // 设置回 false
        $this->userRowPermission->setEdit(false);
        $this->assertFalse($this->userRowPermission->isEdit());
    }

    /**
     * 测试 unlink 属性 getter/setter
     */
    public function testUnlinkGetterSetter(): void
    {
        // 默认值应为 false
        $this->assertFalse($this->userRowPermission->isUnlink());

        // 设置为 true
        $this->userRowPermission->setUnlink(true);
        $this->assertTrue($this->userRowPermission->isUnlink());

        // 设置回 false
        $this->userRowPermission->setUnlink(false);
        $this->assertFalse($this->userRowPermission->isUnlink());
    }

    /**
     * 测试 valid 属性 getter/setter
     */
    public function testValidGetterSetter(): void
    {
        // 默认值应为 false
        $this->assertFalse($this->userRowPermission->isValid());

        // 设置为 true
        $this->userRowPermission->setValid(true);
        $this->assertTrue($this->userRowPermission->isValid());

        // 设置回 false
        $this->userRowPermission->setValid(false);
        $this->assertFalse($this->userRowPermission->isValid());
    }

    /**
     * 测试创建者 getter/setter
     */
    public function testCreatedByGetterSetter(): void
    {
        $createdBy = 'user1';

        $this->userRowPermission->setCreatedBy($createdBy);
        $this->assertEquals($createdBy, $this->userRowPermission->getCreatedBy());

        // 测试 null 值
        $this->userRowPermission->setCreatedBy(null);
        $this->assertNull($this->userRowPermission->getCreatedBy());
    }

    /**
     * 测试更新者 getter/setter
     */
    public function testUpdatedByGetterSetter(): void
    {
        $updatedBy = 'user2';

        $this->userRowPermission->setUpdatedBy($updatedBy);
        $this->assertEquals($updatedBy, $this->userRowPermission->getUpdatedBy());

        // 测试 null 值
        $this->userRowPermission->setUpdatedBy(null);
        $this->assertNull($this->userRowPermission->getUpdatedBy());
    }

    /**
     * 测试创建时间 getter/setter
     */
    public function testCreateTimeGetterSetter(): void
    {
        $now = new \DateTimeImmutable();

        $this->userRowPermission->setCreateTime($now);
        $this->assertEquals($now, $this->userRowPermission->getCreateTime());

        // 测试 null 值
        $this->userRowPermission->setCreateTime(null);
        $this->assertNull($this->userRowPermission->getCreateTime());
    }

    /**
     * 测试更新时间 getter/setter
     */
    public function testUpdateTimeGetterSetter(): void
    {
        $now = new \DateTimeImmutable();

        $this->userRowPermission->setUpdateTime($now);
        $this->assertEquals($now, $this->userRowPermission->getUpdateTime());

        // 测试 null 值
        $this->userRowPermission->setUpdateTime(null);
        $this->assertNull($this->userRowPermission->getUpdateTime());
    }

    /**
     * 测试 hasPermission 方法在不同场景下的行为
     */
    public function testHasPermission(): void
    {
        // 设置初始状态
        $this->userRowPermission->setValid(true);
        $this->userRowPermission->setDeny(false);
        $this->userRowPermission->setView(true);
        $this->userRowPermission->setEdit(false);
        $this->userRowPermission->setUnlink(true);

        // 有查看权限
        $this->assertTrue($this->userRowPermission->hasPermission(PermissionConstantInterface::VIEW));

        // 无编辑权限
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::EDIT));

        // 有解除关联权限
        $this->assertTrue($this->userRowPermission->hasPermission(PermissionConstantInterface::UNLINK));

        // 测试无效权限类型
        $this->assertFalse($this->userRowPermission->hasPermission('invalid_permission'));
    }

    /**
     * 测试当 valid = false 时 hasPermission 方法返回 false
     */
    public function testHasPermissionWithInvalidRecord(): void
    {
        $this->userRowPermission->setValid(false);
        $this->userRowPermission->setDeny(false);
        $this->userRowPermission->setView(true);
        $this->userRowPermission->setEdit(true);
        $this->userRowPermission->setUnlink(true);

        // 虽然设置了权限，但因为记录无效，所有权限都应返回 false
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::VIEW));
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::EDIT));
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::UNLINK));
    }

    /**
     * 测试当 deny = true 时 hasPermission 方法返回 false
     */
    public function testHasPermissionWithDenyRecord(): void
    {
        $this->userRowPermission->setValid(true);
        $this->userRowPermission->setDeny(true);
        $this->userRowPermission->setView(true);
        $this->userRowPermission->setEdit(true);
        $this->userRowPermission->setUnlink(true);

        // 虽然设置了权限，但因为 deny = true，所有权限都应返回 false
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::VIEW));
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::EDIT));
        $this->assertFalse($this->userRowPermission->hasPermission(PermissionConstantInterface::UNLINK));
    }

    /**
     * 创建被测实体的一个实例.
     */
    protected function createEntity(): object
    {
        return new UserRowPermission();
    }

    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'entityClass' => ['entityClass', 'TestEntityClass'];
        yield 'entityId' => ['entityId', '12345'];
        // user 属性为 UserInterface 类型，避免序列化问题，由专门的测试方法覆盖
        yield 'remark' => ['remark', 'Test remark'];
        yield 'deny' => ['deny', true];
        yield 'view' => ['view', true];
        yield 'edit' => ['edit', true];
        yield 'unlink' => ['unlink', true];
        yield 'valid' => ['valid', true];
        yield 'createdBy' => ['createdBy', 'user1'];
        yield 'updatedBy' => ['updatedBy', 'user2'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }
}
