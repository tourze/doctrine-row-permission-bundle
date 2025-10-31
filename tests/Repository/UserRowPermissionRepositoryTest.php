<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(UserRowPermissionRepository::class)]
#[RunTestsInSeparateProcesses]
final class UserRowPermissionRepositoryTest extends AbstractRepositoryTestCase
{
    private UserInterface $user;

    private UserRowPermissionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(UserRowPermissionRepository::class);
        $this->user = $this->createNormalUser('test@example.com', 'password123');

        // 手动加载 fixtures 数据
        $this->loadFixtures();
    }

    /**
     * 手动加载 fixtures 数据
     */
    private function loadFixtures(): void
    {
        $entityManager = self::getEntityManager();

        // 创建测试用户
        for ($i = 1; $i <= 10; ++$i) {
            $user = $this->createNormalUser('test-user-' . $i . '@example.com', 'password123');
            $entityManager->persist($user);
        }

        // 创建权限记录
        for ($i = 1; $i <= 20; ++$i) {
            $permission = new UserRowPermission();
            $permission->setEntityClass('App\Entity\TestEntity_' . uniqid());
            $permission->setEntityId((string) rand(10000, 99999));
            $permission->setUser($this->createNormalUser('test-user-' . ($i % 10 + 1) . '@example.com', 'password123'));
            $permission->setRemark('Test permission remark ' . $i);
            $permission->setDeny(rand(1, 100) <= 20);
            $permission->setView(rand(1, 100) <= 80);
            $permission->setEdit(rand(1, 100) <= 60);
            $permission->setUnlink(rand(1, 100) <= 40);
            $permission->setValid(rand(1, 100) <= 90);

            $entityManager->persist($permission);
        }

        $entityManager->flush();
    }

    public function testFindWithValidIdShouldReturnEntity(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->find($permission->getId());
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertEquals('TestEntity', $result->getEntityClass());
    }

    public function testFindPermissionBehavior(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->findPermission($this->user, 'TestEntity', '123');
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertEquals('TestEntity', $result->getEntityClass());
        $this->assertEquals('123', $result->getEntityId());

        $notFound = $this->repository->findPermission($this->user, 'NonExistentEntity', '999');
        $this->assertNull($notFound);
    }

    public function testFindByPermissionBehavior(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->findByPermission($this->user, 'TestEntity', '123', PermissionConstantInterface::VIEW);
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertTrue($result->isView());

        $notFound = $this->repository->findByPermission($this->user, 'TestEntity', '123', PermissionConstantInterface::EDIT);
        $this->assertNull($notFound);
    }

    public function testFindByPermissionWithInvalidPermissionType(): void
    {
        $result = $this->repository->findByPermission($this->user, 'TestEntity', '123', 'invalid_permission');
        $this->assertNull($result);
    }

    public function testFindBatchPermissions(): void
    {
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($this->user);
        $permission2->setEdit(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $result = $this->repository->findBatchPermissions($this->user, 'TestEntity', ['123', '456']);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $entityIds = array_map(fn ($p) => $p->getEntityId(), $result);
        $this->assertContains('123', $entityIds);
        $this->assertContains('456', $entityIds);
    }

    public function testSave(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $saved = $this->repository->findPermission($this->user, 'TestEntity', '123');
        $this->assertInstanceOf(UserRowPermission::class, $saved);
        $this->assertEquals('TestEntity', $saved->getEntityClass());
    }

    public function testRemove(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);
        $this->repository->remove($permission);

        $result = $this->repository->findPermission($this->user, 'TestEntity', '123');
        $this->assertNull($result);
    }

    public function testQueryWithNullFieldValues(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);
        $permission->setRemark(null);

        $this->repository->save($permission);

        $result = $this->repository->findBy(['remark' => null]);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->getRemark());
    }

    public function testCountWithNullFieldValues(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);
        $permission->setRemark(null);

        $this->repository->save($permission);

        $result = $this->repository->count(['remark' => null]);
        $this->assertEquals(1, $result);
    }

    public function testQueryWithUserAssociation(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->findBy(['user' => $this->user]);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $user = $result[0]->getUser();
        $this->assertNotNull($user);
        $this->assertEquals($this->user->getUserIdentifier(), $user->getUserIdentifier());
    }

    public function testCountWithUserAssociation(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->count(['user' => $this->user]);
        $this->assertEquals(1, $result);
    }

    public function testFindOneByWithOrderBy(): void
    {
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($this->user);
        $permission2->setView(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $result = $this->repository->findOneBy(['entityClass' => 'TestEntity'], ['entityId' => 'DESC']);
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertEquals('456', $result->getEntityId());
    }

    public function testFindByUserAssociation(): void
    {
        $anotherUser = $this->createNormalUser('another@example.com', 'pass456');

        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($anotherUser);
        $permission2->setView(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $result = $this->repository->findBy(['user' => $this->user]);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $user = $result[0]->getUser();
        $this->assertNotNull($user);
        $this->assertEquals($this->user->getUserIdentifier(), $user->getUserIdentifier());
    }

    public function testCountWithUserAssociationMultiple(): void
    {
        $anotherUser = $this->createNormalUser('another@example.com', 'pass456');

        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($anotherUser);
        $permission2->setView(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $countForUser = $this->repository->count(['user' => $this->user]);
        $this->assertEquals(1, $countForUser);

        $countForAnother = $this->repository->count(['user' => $anotherUser]);
        $this->assertEquals(1, $countForAnother);
    }

    public function testFindOneByWithOrderBySorting(): void
    {
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('aaa');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('zzz');
        $permission2->setUser($this->user);
        $permission2->setView(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $result = $this->repository->findOneBy(['entityClass' => 'TestEntity'], ['entityId' => 'ASC']);
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertEquals('aaa', $result->getEntityId());

        $result = $this->repository->findOneBy(['entityClass' => 'TestEntity'], ['entityId' => 'DESC']);
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertEquals('zzz', $result->getEntityId());
    }

    public function testFindByWithMultipleUserAssociations(): void
    {
        $user2 = $this->createNormalUser('user2@example.com', 'pass456');
        $user3 = $this->createNormalUser('user3@example.com', 'pass789');

        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($user2);
        $permission2->setView(true);
        $permission2->setValid(true);

        $permission3 = new UserRowPermission();
        $permission3->setEntityClass('TestEntity');
        $permission3->setEntityId('789');
        $permission3->setUser($user3);
        $permission3->setView(true);
        $permission3->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);
        $this->repository->save($permission3);

        $result = $this->repository->findBy(['user' => $user2]);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $user = $result[0]->getUser();
        $this->assertNotNull($user);
        $this->assertEquals($user2->getUserIdentifier(), $user->getUserIdentifier());
    }

    public function testCountWithMultipleUserAssociations(): void
    {
        $user2 = $this->createNormalUser('user2@example.com', 'pass456');
        $user3 = $this->createNormalUser('user3@example.com', 'pass789');

        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($user2);
        $permission2->setView(true);
        $permission2->setValid(true);

        $permission3 = new UserRowPermission();
        $permission3->setEntityClass('TestEntity');
        $permission3->setEntityId('789');
        $permission3->setUser($user3);
        $permission3->setView(true);
        $permission3->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);
        $this->repository->save($permission3);

        $countForUser1 = $this->repository->count(['user' => $this->user]);
        $this->assertEquals(1, $countForUser1);

        $countForUser2 = $this->repository->count(['user' => $user2]);
        $this->assertEquals(1, $countForUser2);

        $countForUser3 = $this->repository->count(['user' => $user3]);
        $this->assertEquals(1, $countForUser3);
    }

    public function testQueryWithUserAssociationJoin(): void
    {
        $user2 = $this->createNormalUser('user2@example.com', 'pass456');

        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('AnotherEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($user2);
        $permission2->setEdit(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $result = $this->repository->findBy(['user' => $this->user, 'view' => true]);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->isView());
    }

    public function testCountWithUserAssociationJoin(): void
    {
        $user2 = $this->createNormalUser('user2@example.com', 'pass456');

        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('AnotherEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($user2);
        $permission2->setEdit(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $count = $this->repository->count(['user' => $this->user, 'view' => true]);
        $this->assertEquals(1, $count);
    }

    public function testQueryWithNullRemarkField(): void
    {
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);
        $permission1->setRemark(null);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($this->user);
        $permission2->setEdit(true);
        $permission2->setValid(true);
        $permission2->setRemark('有备注');

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $result = $this->repository->findBy(['remark' => null]);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->getRemark());
    }

    public function testCountWithNullRemarkField(): void
    {
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);
        $permission1->setRemark(null);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($this->user);
        $permission2->setEdit(true);
        $permission2->setValid(true);
        $permission2->setRemark('有备注');

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $count = $this->repository->count(['remark' => null]);
        $this->assertEquals(1, $count);
    }

    public function testFindOneByAssociationUserShouldReturnMatchingEntity(): void
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('123');
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->findOneBy(['user' => $this->user]);
        $this->assertInstanceOf(UserRowPermission::class, $result);
        $user = $result->getUser();
        $this->assertNotNull($user);
        $this->assertEquals($this->user->getUserIdentifier(), $user->getUserIdentifier());
    }

    public function testCountByAssociationUserShouldReturnCorrectNumber(): void
    {
        $permission1 = new UserRowPermission();
        $permission1->setEntityClass('TestEntity');
        $permission1->setEntityId('123');
        $permission1->setUser($this->user);
        $permission1->setView(true);
        $permission1->setValid(true);

        $permission2 = new UserRowPermission();
        $permission2->setEntityClass('TestEntity');
        $permission2->setEntityId('456');
        $permission2->setUser($this->user);
        $permission2->setEdit(true);
        $permission2->setValid(true);

        $this->repository->save($permission1);
        $this->repository->save($permission2);

        $count = $this->repository->count(['user' => $this->user]);
        $this->assertEquals(2, $count);
    }

    /**
     * 创建一个新的 UserRowPermission 实体，但不持久化到数据库
     */
    protected function createNewEntity(): object
    {
        $permission = new UserRowPermission();
        $permission->setEntityClass('TestEntity_' . uniqid());
        $permission->setEntityId(uniqid());
        $permission->setUser($this->user);
        $permission->setView(true);
        $permission->setEdit(false);
        $permission->setUnlink(false);
        $permission->setDeny(false);
        $permission->setValid(true);
        $permission->setRemark('Test permission remark ' . uniqid());

        return $permission;
    }

    /**
     * @return ServiceEntityRepository<UserRowPermission>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
