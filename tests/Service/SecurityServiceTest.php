<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Exception\InvalidEntityException;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Request\GrantRowPermissionRequest;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Tests\Service\Support\TestServiceFactory;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(SecurityService::class)]
#[RunTestsInSeparateProcesses]
class SecurityServiceTest extends AbstractIntegrationTestCase
{
    /** @var (UserRowPermissionRepository&MockObject)|null */
    private ?UserRowPermissionRepository $repository = null;

    /** @var (EntityManagerInterface&MockObject)|null */
    private ?EntityManagerInterface $entityManagerMock = null;

    private ?NullLogger $logger = null;

    private ?SecurityService $securityService = null;

    protected function onSetUp(): void
    {
        $this->repository = $this->createMock(UserRowPermissionRepository::class);
        $this->repository->method('findOneBy')->willReturn(null);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->logger = new NullLogger();
        $condManager = TestServiceFactory::createCondManager($this->logger);
        $this->securityService = TestServiceFactory::createSecurityService(
            $this->repository,
            $this->entityManagerMock,
            $this->logger,
            $condManager
        );
    }

    private function securityService(): SecurityService
    {
        if (null === $this->securityService) {
            throw new \LogicException('SecurityService 未初始化');
        }

        return $this->securityService;
    }

    /**
     * 创建带 getId() 方法的实体 mock
     */
    private function createEntityMock(?int $id): object
    {
        $entity = new class($id) {
            public function __construct(private ?int $id)
            {
            }

            public function getId(): ?int
            {
                return $this->id;
            }
        };

        return $entity;
    }

    public function testHasPermissionWithNullUser(): void
    {
        $entity = $this->createEntityMock(1);

        $result = $this->securityService()->hasPermission(null, $entity, PermissionConstantInterface::VIEW);

        $this->assertFalse($result);
    }

    public function testHasPermissionWithEntityWithoutGetId(): void
    {
        $user = $this->createMock(UserInterface::class);
        $entity = new \stdClass();

        $result = $this->securityService()->hasPermission($user, $entity, PermissionConstantInterface::VIEW);

        $this->assertFalse($result);
    }

    public function testHasPermissionWithNullEntityId(): void
    {
        $user = $this->createMock(UserInterface::class);
        $entity = $this->createEntityMock(null);

        $result = $this->securityService()->hasPermission($user, $entity, PermissionConstantInterface::VIEW);

        $this->assertFalse($result);
    }

    public function testGetQueryConditions(): void
    {
        $user = $this->createMock(UserInterface::class);

        $result = $this->securityService()->getQueryConditions('TestEntity', 'alias', $user);

        $this->assertIsArray($result);
    }

    public function testGetQueryConditionsWithEmptyPermissions(): void
    {
        $user = $this->createMock(UserInterface::class);

        $result = $this->securityService()->getQueryConditions('TestEntity', 'alias', $user, []);

        $this->assertIsArray($result);
    }

    public function testGrantRowPermissionWithoutGetIdMethod(): void
    {
        $request = new GrantRowPermissionRequest();
        $request->setUser($this->createMock(UserInterface::class));
        $request->setObject(new \stdClass());

        $this->expectException(InvalidEntityException::class);
        $this->expectExceptionMessage('实体必须有 getId 方法');

        $this->securityService()->grantRowPermission($request);
    }

    public function testGrantRowPermissionWithNullEntityId(): void
    {
        $request = new GrantRowPermissionRequest();
        $request->setUser($this->createMock(UserInterface::class));
        $request->setObject($this->createEntityMock(null));

        $this->expectException(InvalidEntityException::class);
        $this->expectExceptionMessage('实体 ID 不能为空');

        $this->securityService()->grantRowPermission($request);
    }

    public function testGrantPermission(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        $entity = $this->createEntityMock(123);
        $permissions = [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
        ];

        $result = $this->securityService()->grantPermission($user, $entity, $permissions);

        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertTrue($result->isView());
        $this->assertFalse($result->isEdit());
        $this->assertTrue($result->isValid());
    }

    public function testGrantBatchPermissions(): void
    {
        $user = $this->createNormalUser('test2@example.com', 'password');
        $entities = [$this->createEntityMock(124), $this->createEntityMock(125)];
        $permissions = [PermissionConstantInterface::VIEW => true];

        $results = $this->securityService()->grantBatchPermissions($user, $entities, $permissions);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(UserRowPermission::class, $results);
    }

    public function testGrantRowPermissionWithNewRecord(): void
    {
        $user = $this->createNormalUser('test3@example.com', 'password');
        $entity = $this->createEntityMock(126);

        $request = new GrantRowPermissionRequest();
        $request->setUser($user);
        $request->setObject($entity);
        $request->setView(true);
        $request->setEdit(false);
        $request->setUnlink(true);
        $request->setDeny(false);

        $result = $this->securityService()->grantRowPermission($request);

        $this->assertInstanceOf(UserRowPermission::class, $result);
        $this->assertTrue($result->isView());
        $this->assertFalse($result->isEdit());
        $this->assertTrue($result->isUnlink());
        $this->assertFalse($result->isDeny());
        $this->assertTrue($result->isValid());
    }
}
