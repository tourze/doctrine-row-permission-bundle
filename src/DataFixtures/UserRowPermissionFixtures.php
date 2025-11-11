<?php

declare(strict_types=1);

namespace Tourze\DoctrineRowPermissionBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\DoctrineRowPermissionBundle\Entity\UserRowPermission;
use Tourze\UserServiceContracts\UserManagerInterface;

#[When(env: 'test')]
#[When(env: 'dev')]
class UserRowPermissionFixtures extends Fixture implements FixtureGroupInterface
{
    public const PERMISSION_REFERENCE_PREFIX = 'user-row-permission-';

    public function __construct(private readonly UserManagerInterface $userManager)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 创建测试用户
        $testUsers = [];
        for ($i = 1; $i <= 10; ++$i) {
            $user = $this->userManager->createUser('test-user-' . $i);
            // 注意：不要持久化用户，用户应该由UserManager自己管理
            // 如果是TestEntityUserManager，它会自动处理持久化
            // 如果是InMemoryUserManager，用户只在内存中
            $this->userManager->saveUser($user);
            $testUsers[] = $user;
        }

        for ($i = 1; $i <= 20; ++$i) {
            $permission = new UserRowPermission();

            $permission->setEntityClass('App\Entity\TestEntity_' . uniqid());
            $permission->setEntityId((string) $faker->randomNumber(8));

            // 随机选择一个用户
            $user = $testUsers[$i % count($testUsers)];
            $permission->setUser($user);

            $permission->setRemark($faker->sentence());
            $permission->setDeny($faker->boolean(20));

            if (!$permission->isDeny()) {
                $permission->setView($faker->boolean(80));
                $permission->setEdit($faker->boolean(60));
                $permission->setUnlink($faker->boolean(40));
            }

            $permission->setValid($faker->boolean(90));

            $createTime = CarbonImmutable::now()->modify('-' . rand(1, 60) . ' days');
            $permission->setCreateTime($createTime);
            $permission->setUpdateTime($createTime->modify('+' . rand(0, 10) . ' hours'));

            $manager->persist($permission);
            $this->addReference(self::PERMISSION_REFERENCE_PREFIX . $i, $permission);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'doctrine_row_permission',
        ];
    }
}
