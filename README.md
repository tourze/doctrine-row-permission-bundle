# Doctrine Row Permission Bundle

这个 Symfony bundle 用于提供行级别的数据权限控制，作为 RBAC 权限体系的补充，实现对数据的精确控制。

## 安装

通过 Composer 安装:

```bash
composer require tourze/doctrine-row-permission-bundle
```

## 功能特点

- 为特定用户授予对特定实体行的访问权限
- 支持多种权限类型：查看、编辑、解除关联
- 支持同时禁止访问特定实体
- 提供查询构建器条件，轻松应用于 Doctrine 查询
- 支持缓存机制，提高性能

## 配置

在 `config/bundles.php` 中注册 bundle:

```php
return [
    // ...
    Tourze\DoctrineRowPermissionBundle\DoctrineRowPermissionBundle::class => ['all' => true],
];
```

## 使用方法

### 授予权限

```php
// 通过依赖注入获取服务
use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class YourService 
{
    public function grantPermission(
        RowPermissionInterface $permissionService,
        User $user,
        Product $product
    ): void {
        // 授予单个实体的权限
        $permissionService->grantPermission($user, $product, [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
            PermissionConstantInterface::UNLINK => false,
        ]);
        
        // 或批量授予权限
        $products = $productRepository->findAll();
        $permissionService->grantBatchPermissions($user, $products, [
            PermissionConstantInterface::VIEW => true,
        ]);
    }
}
```

### 检查权限

```php
// 通过依赖注入获取服务
use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class YourService 
{
    public function checkAccess(
        RowPermissionInterface $permissionService,
        User $user,
        Product $product
    ): bool {
        // 检查用户是否有查看权限
        if ($permissionService->hasPermission($user, $product, PermissionConstantInterface::VIEW)) {
            return true;
        }
        
        return false;
    }
}
```

### 在查询中使用权限过滤

```php
use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class ProductRepository extends ServiceEntityRepository
{
    private RowPermissionInterface $permissionService;
    
    public function __construct(
        ManagerRegistry $registry,
        RowPermissionInterface $permissionService
    ) {
        parent::__construct($registry, Product::class);
        $this->permissionService = $permissionService;
    }
    
    public function findAllWithPermission(User $user): array
    {
        $qb = $this->createQueryBuilder('p');
        
        // 获取权限条件
        $conditions = $this->permissionService->getQueryConditions(
            Product::class,
            'p',
            $user,
            [PermissionConstantInterface::VIEW]
        );
        
        // 应用条件到查询构建器
        foreach ($conditions as [$operator, $condition, $parameters]) {
            $qb->andWhere($condition);
            foreach ($parameters as $name => $value) {
                $qb->setParameter($name, $value);
            }
        }
        
        return $qb->getQuery()->getResult();
    }
}
```

## 许可证

MIT
