<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @template-extends AbstractRepositoryTestCase<UserRowPermission>
 * @internal
 */
#[CoversClass(UserRowPermissionRepository::class)]
#[RunTestsInSeparateProcesses]
final class UserRowPermissionRepositoryTest extends AbstractRepositoryTestCase
{
    private UserRowPermissionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(UserRowPermissionRepository::class);
    }

    protected function createNewEntity(): object
    {
        $user = $this->createUser('testuser', 'password', []);
        $entity = new UserRowPermission();
        $entity->setUser($user);
        $entity->setEntityClass('TestEntity');
        $entity->setEntityId('test-id');
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): UserRowPermissionRepository
    {
        return $this->repository;
    }

    public function testFindPermission(): void
    {
        $user = $this->createUser('testuser', 'password', []);
        $entityClass = 'TestEntity';
        $entityId = '123';

        // 创建测试权限
        $permission = new UserRowPermission();
        $permission->setUser($user);
        $permission->setEntityClass($entityClass);
        $permission->setEntityId($entityId);
        $permission->setView(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        // 测试查找
        $result = $this->repository->findPermission($user, $entityClass, $entityId);

        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertSame($entityClass, $result->getEntityClass());
        $this->assertSame($entityId, $result->getEntityId());
    }

    public function testFindByPermissionWithValidPermission(): void
    {
        $user = $this->createUser('testuser2', 'password', []);
        $entityClass = 'TestEntity';
        $entityId = '456';

        $permission = new UserRowPermission();
        $permission->setUser($user);
        $permission->setEntityClass($entityClass);
        $permission->setEntityId($entityId);
        $permission->setEdit(true);
        $permission->setValid(true);

        $this->repository->save($permission);

        $result = $this->repository->findByPermission($user, $entityClass, $entityId, PermissionConstantInterface::EDIT);

        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertTrue($result->isEdit());
    }

    public function testFindByPermissionWithInvalidPermission(): void
    {
        $user = $this->createUser('testuser3', 'password', []);

        $result = $this->repository->findByPermission($user, 'TestEntity', '789', 'invalid_permission');

        $this->assertNull($result);
    }

    public function testFindBatchPermissions(): void
    {
        $user = $this->createUser('testuser4', 'password', []);
        $entityClass = 'TestEntity';
        $entityIds = ['100', '200', '300'];

        // 创建多个权限
        foreach ($entityIds as $entityId) {
            $permission = new UserRowPermission();
            $permission->setUser($user);
            $permission->setEntityClass($entityClass);
            $permission->setEntityId($entityId);
            $permission->setView(true);
            $permission->setValid(true);

            $this->repository->save($permission, false);
        }
        self::getEntityManager()->flush();

        // 测试批量查找
        $results = $this->repository->findBatchPermissions($user, $entityClass, $entityIds);

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(UserRowPermission::class, $result);
            $this->assertContains($result->getEntityId(), $entityIds);
        }
    }

    public function testSaveAndRemove(): void
    {
        $user = $this->createUser('testuser5', 'password', []);
        $permission = new UserRowPermission();
        $permission->setUser($user);
        $permission->setEntityClass('TestEntity');
        $permission->setEntityId('999');
        $permission->setValid(true);

        // 测试保存
        $this->repository->save($permission);
        $this->assertNotNull($permission->getId());

        // 测试删除
        $this->repository->remove($permission);

        $result = $this->repository->findPermission($user, 'TestEntity', '999');
        $this->assertNull($result);
    }
}
