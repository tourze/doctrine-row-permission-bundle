<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Exception\InvalidEntityException;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Request\GrantRowPermissionRequest;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(SecurityService::class)]
#[RunTestsInSeparateProcesses]
class SecurityServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试需要的设置
    }

    public function testHasPermissionWithNullUser(): void
    {
        $securityService = self::getService(SecurityService::class);
        $entity = $this->createEntityMock(1);

        $result = $securityService->hasPermission(null, $entity, PermissionConstantInterface::VIEW);

        $this->assertFalse($result);
    }

    public function testHasPermissionWithEntityWithoutGetId(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createMock(UserInterface::class);
        $entity = new \stdClass();

        $result = $securityService->hasPermission($user, $entity, PermissionConstantInterface::VIEW);

        $this->assertFalse($result);
    }

    public function testHasPermissionWithNullEntityId(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createMock(UserInterface::class);
        $entity = $this->createEntityMock(null);

        $result = $securityService->hasPermission($user, $entity, PermissionConstantInterface::VIEW);

        $this->assertFalse($result);
    }

    public function testGetQueryConditions(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createMock(UserInterface::class);

        $result = $securityService->getQueryConditions('TestEntity', 'alias', $user);

        $this->assertIsArray($result);
    }

    public function testGetQueryConditionsWithEmptyPermissions(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createMock(UserInterface::class);

        $result = $securityService->getQueryConditions('TestEntity', 'alias', $user, []);

        $this->assertIsArray($result);
    }

    public function testGrantRowPermissionWithoutGetIdMethod(): void
    {
        $securityService = self::getService(SecurityService::class);
        $request = new GrantRowPermissionRequest();
        $request->setUser($this->createMock(UserInterface::class));
        $request->setObject(new \stdClass());

        $this->expectException(InvalidEntityException::class);
        $this->expectExceptionMessage('实体必须有 getId 方法');

        $securityService->grantRowPermission($request);
    }

    public function testGrantRowPermissionWithNullEntityId(): void
    {
        $securityService = self::getService(SecurityService::class);
        $request = new GrantRowPermissionRequest();
        $request->setUser($this->createMock(UserInterface::class));
        $request->setObject($this->createEntityMock(null));

        $this->expectException(InvalidEntityException::class);
        $this->expectExceptionMessage('实体 ID 不能为空');

        $securityService->grantRowPermission($request);
    }

    public function testGrantPermission(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createNormalUser('test@example.com', 'password');
        $entity = $this->createEntityMock(123);
        $permissions = [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
        ];

        $result = $securityService->grantPermission($user, $entity, $permissions);

        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertTrue($result->isView());
        $this->assertFalse($result->isEdit());
        $this->assertTrue($result->isValid());
    }

    public function testGrantBatchPermissions(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createNormalUser('test2@example.com', 'password');
        $entities = [$this->createEntityMock(124), $this->createEntityMock(125)];
        $permissions = [PermissionConstantInterface::VIEW => true];

        $results = $securityService->grantBatchPermissions($user, $entities, $permissions);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(UserRowPermission::class, $results);
    }

    public function testGrantRowPermissionWithNewRecord(): void
    {
        $securityService = self::getService(SecurityService::class);
        $user = $this->createNormalUser('test3@example.com', 'password');
        $entity = $this->createEntityMock(126);

        $request = new GrantRowPermissionRequest();
        $request->setUser($user);
        $request->setObject($entity);
        $request->setView(true);
        $request->setEdit(false);
        $request->setUnlink(true);
        $request->setDeny(false);

        $result = $securityService->grantRowPermission($request);

        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertTrue($result->isView());
        $this->assertFalse($result->isEdit());
        $this->assertTrue($result->isUnlink());
        $this->assertFalse($result->isDeny());
        $this->assertTrue($result->isValid());
    }

    private function createEntityMock(?int $id): object
    {
        return new class($id) {
            private ?int $id;

            public function __construct(?int $id)
            {
                $this->id = $id;
            }

            public function getId(): ?int
            {
                return $this->id;
            }
        };
    }
}
