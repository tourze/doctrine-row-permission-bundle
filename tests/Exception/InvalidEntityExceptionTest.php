<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrineRowPermissionBundle\Exception\InvalidEntityException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidEntityException::class)]
final class InvalidEntityExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new InvalidEntityException('测试消息');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('测试消息', $exception->getMessage());
    }

    public function testExceptionWithMessage(): void
    {
        $message = '实体必须有 getId 方法';
        $exception = new InvalidEntityException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
