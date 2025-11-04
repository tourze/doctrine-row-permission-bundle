<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\Tests\Service\Support;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Tourze\DoctrineRowPermissionBundle\Repository\UserRowPermissionRepository;
use Tourze\DoctrineRowPermissionBundle\Service\CondManager;
use Tourze\DoctrineRowPermissionBundle\Service\SecurityService;

final class TestServiceFactory
{
    private function __construct()
    {
    }

    public static function createCondManager(LoggerInterface $logger): CondManager
    {
        return new CondManager($logger);
    }

    public static function createSecurityService(
        UserRowPermissionRepository $repository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CondManager $condManager,
        ?CacheItemPoolInterface $cache = null,
    ): SecurityService {
        return new SecurityService($repository, $entityManager, $logger, $condManager, $cache);
    }
}
