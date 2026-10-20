<?php

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\Dogs;
use App\Entity\Adoption;
use App\Entity\User;
use App\Repository\DogsRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixturesTest extends KernelTestCase
{
    public function testFixturesCreateDemoUsersAndDogs(): void
    {
        $container = static::getContainer();
        $config = ORMSetup::createAttributeMetadataConfig([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $manager = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            $config,
        );

        try {
            (new SchemaTool($manager))->createSchema([
                $manager->getClassMetadata(User::class),
                $manager->getClassMetadata(Dogs::class),
                $manager->getClassMetadata(Adoption::class),
            ]);
            $container->set('doctrine.orm.default_entity_manager', $manager);
            $container->get(AppFixtures::class)->load($manager);
            $manager->clear();

            $users = $container->get(UserRepository::class)->findAll();
            self::assertCount(3, $users);
            $passwordHasher = $container->get(UserPasswordHasherInterface::class);
            foreach ($users as $user) {
                self::assertSame($user->getEmail(), $user->getUserIdentifier());
                self::assertNotSame('password123', $user->getPassword());
                self::assertTrue($passwordHasher->isPasswordValid($user, 'password123'));
            }

            $userRepository = $container->get(UserRepository::class);
            self::assertContains('ROLE_ADMIN', $userRepository->findOneBy(['email' => 'admin@example.com'])->getRoles());
            self::assertContains('ROLE_EMPLOYEE', $userRepository->findOneBy(['email' => 'membre@example.com'])->getRoles());
            self::assertContains('ROLE_USER', $userRepository->findOneBy(['email' => 'user@example.com'])->getRoles());
            $dogs = $container->get(DogsRepository::class);
            self::assertCount(6, $dogs->findAll());
            self::assertCount(3, $dogs->findDogDisponible());
            self::assertCount(2, $dogs->findBy(['status' => 'En Attente']));
            self::assertCount(1, $dogs->findBy(['status' => 'Adopte']));
            self::assertSame(3, $manager->getConnection()->fetchOne('SELECT COUNT(*) FROM Adoption'));
        } finally {
            $manager->close();
            $manager->getConnection()->close();
        }
    }
}
