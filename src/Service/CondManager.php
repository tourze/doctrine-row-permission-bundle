<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Service;

use Doctrine\DBAL\Connection;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

/**
 * 权限条件管理器
 */
#[WithMonologChannel(channel: 'doctrine_row_permission')]
#[Autoconfigure(public: true)]
readonly class CondManager
{
    /**
     * @param LoggerInterface $logger 日志接口
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 用户行数据权限控制，生成查询条件
     *
     * @param string             $entityClass    实体类名
     * @param string             $alias          查询别名
     * @param UserInterface|null $user           用户对象
     * @param array<string>      $rowPermissions 权限类型列表
     *
     * @return array<array{string, string, array<string, mixed>}> 查询条件数组
     */
    public function getUserRowWhereStatements(
        string $entityClass,
        string $alias,
        ?UserInterface $user,
        array $rowPermissions = [PermissionConstantInterface::VIEW],
    ): array {
        $result = [];

        if (null === $user) {
            $this->logger->debug('未提供用户对象，跳过权限控制');

            return $result;
        }

        try {
            $dqlTableName = UserRowPermission::class;
            $paramIndex = 0;
            $paramSuffix = md5($entityClass . $alias . spl_object_id($user));

            // 明确不允许访问的数据（deny 标记）- 这个条件是最优先的
            $denyParamName = 'user_deny_' . $paramSuffix;
            $result[] = [
                'AND',
                "{$alias}.id NOT IN (
                    SELECT deny_perm.entityId 
                    FROM {$dqlTableName} deny_perm 
                    WHERE deny_perm.entityClass = :entity_class_{$paramSuffix} 
                    AND deny_perm.user = :{$denyParamName} 
                    AND deny_perm.deny = true
                    AND deny_perm.valid = true
                )",
                [
                    'entity_class_' . $paramSuffix => $entityClass,
                    $denyParamName => $user,
                ],
            ];

            // 权限条件
            $permConditions = [];
            $parameters = [];

            // 构建每种权限的条件
            foreach ($rowPermissions as $permission) {
                if (!in_array($permission, [
                    PermissionConstantInterface::VIEW,
                    PermissionConstantInterface::EDIT,
                    PermissionConstantInterface::UNLINK,
                ], true)) {
                    $this->logger->warning('忽略未知权限类型', ['permission' => $permission]);
                    continue;
                }

                $paramName = 'user_' . $permission . '_' . $paramSuffix;
                $permParamName = 'perm_' . $permission . '_' . $paramSuffix;

                $permConditions[] = "{$alias}.id IN (
                    SELECT {$permission}_perm.entityId 
                    FROM {$dqlTableName} {$permission}_perm 
                    WHERE {$permission}_perm.entityClass = :entity_class_{$paramSuffix} 
                    AND {$permission}_perm.user = :{$paramName} 
                    AND {$permission}_perm.{$permission} = true
                    AND {$permission}_perm.valid = true
                )";

                $parameters[$paramName] = $user;
            }

            // 如果有权限条件，则添加到结果中
            if ([] !== $permConditions) {
                $result[] = [
                    'OR',
                    '(' . implode(' OR ', $permConditions) . ')',
                    $parameters,
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->error('生成权限条件时出错', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            $result = [];
        }

        return $result;
    }

    /**
     * 获取参数化的 SQL 片段 (更安全，但可能不适用于所有情况)
     *
     * @param string             $entityClass    实体类名
     * @param string             $alias          查询别名
     * @param UserInterface|null $user           用户对象
     * @param array<string>      $rowPermissions 权限类型列表
     * @param Connection         $connection     数据库连接
     *
     * @return array{string, array<string, mixed>} [string $sql, array $parameters]
     */
    public function getParameterizedSql(
        string $entityClass,
        string $alias,
        ?UserInterface $user,
        Connection $connection,
        array $rowPermissions = [PermissionConstantInterface::VIEW],
    ): array {
        $conditions = $this->getUserRowWhereStatements($entityClass, $alias, $user, $rowPermissions);

        $sql = [];
        $parameters = [];

        foreach ($conditions as [$operator, $condition, $params]) {
            $sql[] = $operator . ' ' . $condition;
            foreach ($params as $name => $value) {
                if ($value instanceof UserInterface) {
                    // 处理用户对象参数
                    $parameters[$name] = $value->getUserIdentifier();
                } else {
                    $parameters[$name] = $value;
                }
            }
        }

        return [implode(' ', $sql), $parameters];
    }
}
