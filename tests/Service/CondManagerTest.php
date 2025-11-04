<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Service\CondManager;
use Tourze\DoctrineRowPermissionBundle\Tests\Service\Support\TestServiceFactory;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(CondManager::class)]
#[RunTestsInSeparateProcesses]
class CondManagerTest extends AbstractIntegrationTestCase
{
    private ?CondManager $condManager = null;

    protected function onSetUp(): void
    {
        $this->condManager = TestServiceFactory::createCondManager(new NullLogger());
    }

    private function condManager(): CondManager
    {
        if (null === $this->condManager) {
            throw new \LogicException('CondManager 未初始化');
        }

        return $this->condManager;
    }

    public function testGetUserRowWhereStatementsWithNullUser(): void
    {
        $result = $this->condManager()->getUserRowWhereStatements(
            'TestEntity',
            'alias',
            null
        );

        $this->assertEmpty($result);
    }

    public function testGetUserRowWhereStatementsWithUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('test_user');

        $result = $this->condManager()->getUserRowWhereStatements(
            'TestEntity',
            'alias',
            $user,
            [PermissionConstantInterface::VIEW]
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // 检查第一个条件（deny条件）
        $this->assertSame('AND', $result[0][0]);
        $this->assertStringContainsString('alias.id NOT IN', $result[0][1]);
        $this->assertIsArray($result[0][2]);
        $keys = array_keys($result[0][2]);
        $this->assertStringContainsString('entity_class_', $keys[0]);

        // 检查第二个条件（权限条件）
        $this->assertSame('OR', $result[1][0]);
        $this->assertStringContainsString('alias.id IN', $result[1][1]);
    }

    public function testGetUserRowWhereStatementsWithMultiplePermissions(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('test_user');

        $result = $this->condManager()->getUserRowWhereStatements(
            'TestEntity',
            'alias',
            $user,
            [PermissionConstantInterface::VIEW, PermissionConstantInterface::EDIT]
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // 检查权限条件中包含多个权限
        $permissionCondition = $result[1][1];
        $this->assertStringContainsString('view_perm.view = true', $permissionCondition);
        $this->assertStringContainsString('edit_perm.edit = true', $permissionCondition);
    }

    public function testGetUserRowWhereStatementsWithInvalidPermission(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('test_user');

        $result = $this->condManager()->getUserRowWhereStatements(
            'TestEntity',
            'alias',
            $user,
            ['invalid']
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // 只有deny条件，没有权限条件
    }

    public function testGetParameterizedSql(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('test_user');

        $connection = $this->createMock(Connection::class);

        [$sql, $parameters] = $this->condManager()->getParameterizedSql(
            'TestEntity',
            'alias',
            $user,
            $connection,
            [PermissionConstantInterface::VIEW]
        );

        $this->assertIsString($sql);
        $this->assertIsArray($parameters);
        $this->assertStringContainsString('AND', $sql);
        $this->assertStringContainsString('OR', $sql);

        // 检查参数中用户标识符被正确转换
        $userParamFound = false;
        foreach ($parameters as $value) {
            if ('test_user' === $value) {
                $userParamFound = true;
                break;
            }
        }
        $this->assertTrue($userParamFound);
    }

    public function testGetParameterizedSqlWithNullUser(): void
    {
        $connection = $this->createMock(Connection::class);

        [$sql, $parameters] = $this->condManager()->getParameterizedSql(
            'TestEntity',
            'alias',
            null,
            $connection
        );

        $this->assertSame('', $sql);
        $this->assertEmpty($parameters);
    }
}
