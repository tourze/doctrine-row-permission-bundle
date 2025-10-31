# Doctrine Row Permission Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-row-permission-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-row-permission-bundle)
[![License](https://img.shields.io/packagist/l/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-row-permission-bundle)

基于 Doctrine ORM 的 Symfony Bundle，提供行级权限控制系统，
作为 RBAC 权限系统的补充，实现实体级别的精确数据访问控制。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
  - [系统要求](#系统要求)
  - [通过 Composer 安装](#通过-composer-安装)
  - [注册 Bundle](#注册-bundle)
- [快速开始](#快速开始)
  - [基本权限管理](#基本权限管理)
  - [查询集成](#查询集成)
  - [批量操作](#批量操作)
- [配置](#配置)
  - [缓存设置](#缓存设置)
  - [自定义权限逻辑](#自定义权限逻辑)
- [权限类型](#权限类型)
- [安全性](#安全性)
- [贡献指南](#贡献指南)
  - [开发环境](#开发环境)
- [许可证](#许可证)

## 功能特性

- 🔒 **行级安全** - 控制对特定实体实例的访问权限
- 🎯 **多种权限类型** - 支持查看、编辑、删除等操作权限
- 🚫 **显式拒绝** - 支持最高优先级的显式访问拒绝
- 🔍 **查询集成** - Doctrine QueryBuilder 集成过滤查询
- ⚡ **性能缓存** - 内置缓存提升权限检查性能
- 📦 **批量操作** - 高效的批量权限管理

## 安装

### 系统要求

- PHP 8.1+
- Symfony 7.3+
- Doctrine ORM 3.0+

### 通过 Composer 安装

```bash
composer require tourze/doctrine-row-permission-bundle
```

### 注册 Bundle

添加到 `config/bundles.php`：

```php
return [
    // ...
    Tourze\DoctrineRowPermissionBundle\DoctrineRowPermissionBundle::class => ['all' => true],
];
```

## 快速开始

### 基本权限管理

```php
<?php

use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class ProductService
{
    public function __construct(
        private RowPermissionInterface $permissionService
    ) {}

    // 授予单个实体权限
    public function grantUserAccess(User $user, Product $product): void
    {
        $this->permissionService->grantPermission($user, $product, [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
        ]);
    }

    // 检查权限
    public function canUserViewProduct(User $user, Product $product): bool
    {
        return $this->permissionService->hasPermission(
            $user, 
            $product, 
            PermissionConstantInterface::VIEW
        );
    }
}
```

### 查询集成

```php
<?php

use Doctrine\ORM\EntityRepository;
use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;

class ProductRepository extends EntityRepository
{
    public function __construct(
        private RowPermissionInterface $permissionService
    ) {}

    public function findUserAccessibleProducts(User $user): array
    {
        $qb = $this->createQueryBuilder('p');
        
        // 应用权限过滤
        $conditions = $this->permissionService->getQueryConditions(
            Product::class,
            'p',
            $user,
            [PermissionConstantInterface::VIEW]
        );
        
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

### 批量操作

```php
<?php

// 批量授予多个实体权限
$this->permissionService->grantBatchPermissions($user, $products, [
    PermissionConstantInterface::VIEW => true,
]);
```

## 配置

### 缓存设置

配置缓存以提升性能：

```yaml
# config/services.yaml
services:
    Tourze\DoctrineRowPermissionBundle\Service\SecurityService:
        arguments:
            $cache: '@cache.app'
```

### 自定义权限逻辑

实现自定义权限逻辑：

```php
<?php

use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;

class CustomPermissionService implements RowPermissionInterface
{
    public function hasPermission(?UserInterface $user, object $entity, string $permission): bool
    {
        // 自定义逻辑
    }
    
    // 实现其他接口方法...
}
```

## 权限类型

可用的权限常量：

- `PermissionConstantInterface::VIEW` - 查看权限
- `PermissionConstantInterface::EDIT` - 编辑权限
- `PermissionConstantInterface::UNLINK` - 删除/解除关联权限
- `PermissionConstantInterface::DENY` - 显式拒绝（最高优先级）

## 安全性

此 Bundle 实现行级安全（RLS）模式。安全考虑事项：

- 授予权限前始终验证用户输入
- 对敏感操作使用显式拒绝
- 适当缓存权限检查结果
- 定期审计权限分配

## 贡献指南

我们欢迎您的贡献！请遵循以下步骤：

1. Fork 本仓库
2. 创建您的特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交您的更改 (`git commit -m 'Add amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 开启一个 Pull Request

### 开发环境

```bash
# 安装依赖
composer install

# 运行测试（从 monorepo 根目录）
./vendor/bin/phpunit packages/doctrine-row-permission-bundle/tests

# 运行静态分析（从 monorepo 根目录）
./vendor/bin/phpstan analyse packages/doctrine-row-permission-bundle

# 运行包检查（从 monorepo 根目录）
bin/console app:check-packages doctrine-row-permission-bundle
```

## 许可证

MIT 许可证（MIT）。请查看 [许可证文件](LICENSE) 了解更多信息。