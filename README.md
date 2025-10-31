# Doctrine Row Permission Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-row-permission-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-row-permission-bundle)
[![License](https://img.shields.io/packagist/l/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/doctrine-row-permission-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-row-permission-bundle)

A Symfony Bundle that provides row-level permission control system based on Doctrine ORM, 
serving as a complement to RBAC permission systems for precise data access control at entity level.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
  - [Requirements](#requirements)
  - [Install via Composer](#install-via-composer)
  - [Register Bundle](#register-bundle)
- [Quick Start](#quick-start)
  - [Basic Permission Management](#basic-permission-management)
  - [Query Integration](#query-integration)
  - [Batch Operations](#batch-operations)
- [Configuration](#configuration)
  - [Cache Setup](#cache-setup)
  - [Custom Permission Logic](#custom-permission-logic)
- [Permission Types](#permission-types)
- [Security](#security)
- [Contributing](#contributing)
  - [Development](#development)
- [License](#license)

## Features

- 🔒 **Row-Level Security** - Control access to specific entity instances
- 🎯 **Multiple Permission Types** - Support view, edit, delete operations  
- 🚫 **Explicit Deny** - Support for explicit access denial with highest priority
- 🔍 **Query Integration** - Doctrine QueryBuilder integration for filtered queries
- ⚡ **Performance Cache** - Built-in caching for improved permission checking
- 📦 **Batch Operations** - Efficient batch permission management

## Installation

### Requirements

- PHP 8.1+
- Symfony 7.3+  
- Doctrine ORM 3.0+

### Install via Composer

```bash
composer require tourze/doctrine-row-permission-bundle
```

### Register Bundle

Add to `config/bundles.php`:

```php
return [
    // ...
    Tourze\DoctrineRowPermissionBundle\DoctrineRowPermissionBundle::class => ['all' => true],
];
```

## Quick Start

### Basic Permission Management

```php
<?php

use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;

class ProductService
{
    public function __construct(
        private RowPermissionInterface $permissionService
    ) {}

    // Grant single entity permission
    public function grantUserAccess(User $user, Product $product): void
    {
        $this->permissionService->grantPermission($user, $product, [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
        ]);
    }

    // Check permission
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

### Query Integration

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
        
        // Apply permission filters
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

### Batch Operations

```php
<?php

// Grant permissions to multiple entities at once
$this->permissionService->grantBatchPermissions($user, $products, [
    PermissionConstantInterface::VIEW => true,
]);
```

## Configuration

### Cache Setup

Configure cache for better performance:

```yaml
# config/services.yaml
services:
    Tourze\DoctrineRowPermissionBundle\Service\SecurityService:
        arguments:
            $cache: '@cache.app'
```

### Custom Permission Logic

Implement custom permission logic:

```php
<?php

use Tourze\DoctrineRowPermissionBundle\Interface\RowPermissionInterface;

class CustomPermissionService implements RowPermissionInterface
{
    public function hasPermission(?UserInterface $user, object $entity, string $permission): bool
    {
        // Custom logic here
    }
    
    // Implement other interface methods...
}
```

## Permission Types

Available permission constants:

- `PermissionConstantInterface::VIEW` - View permission  
- `PermissionConstantInterface::EDIT` - Edit permission
- `PermissionConstantInterface::UNLINK` - Delete/unlink permission
- `PermissionConstantInterface::DENY` - Explicit deny (highest priority)

## Security

This bundle implements row-level security (RLS) patterns. For security considerations:

- Always validate user input before granting permissions
- Use explicit deny for sensitive operations
- Cache permission checks appropriately 
- Regular audit of permission assignments

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)  
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development

```bash
# Install dependencies
composer install

# Run tests (from monorepo root)
./vendor/bin/phpunit packages/doctrine-row-permission-bundle/tests

# Run static analysis (from monorepo root)  
./vendor/bin/phpstan analyse packages/doctrine-row-permission-bundle

# Run package checks (from monorepo root)
bin/console app:check-packages doctrine-row-permission-bundle
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
