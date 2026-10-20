<?php

namespace App\Tests\Functional;

use App\Entity\Dogs;
use App\Enum\DogsStatus;
use App\Repository\DogsRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DogsRepositoryTest extends KernelTestCase
{
    public function testFindDogDisponible(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $config = ORMSetup::createAttributeMetadataConfig([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $manager = new EntityManager($connection, $config);

        try {
            (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(Dogs::class)]);
            $registry = $this->createMock(ManagerRegistry::class);
            $registry->expects(self::once())->method('getManagerForClass')->with(Dogs::class)->willReturn($manager);
            $repository = new DogsRepository($registry);

            self::assertSame([], $repository->findDogDisponible());

            foreach (DogsStatus::cases() as $status) {
                $dog = (new Dogs())
                    ->setNom($status->value)
                    ->setEspece('Chien')
                    ->setRace('Croise')
                    ->setAge(3)
                    ->setDescription('Chien de demonstration')
                    ->setStatus($status->value);
                $manager->persist($dog);
            }

            $manager->flush();
            $manager->clear();
            $dogs = $repository->findDogDisponible();

            self::assertCount(1, $dogs);
            self::assertSame(DogsStatus::Disponible->value, $dogs[0]->getStatus());
        } finally {
            $manager->close();
            $connection->close();
        }
    }
}
