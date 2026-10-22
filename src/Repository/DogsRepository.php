<?php

namespace App\Repository;

use App\Entity\Dogs;
use App\Enum\DogsStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dogs>
 */
class DogsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dogs::class);
    }

    /** @return Dogs[] */
    public function findDogDisponible(): array
    {
        return $this->findBy([
            'status' => DogsStatus::Disponible->value,
        ]);
    }

    /** @return Dogs[] */
    public function search(string $query = '', ?string $status = null): array
    {
        $builder = $this->createQueryBuilder('d')->orderBy('d.Nom', 'ASC');
        if (trim($query) !== '') {
            $builder
                ->andWhere('d.Nom LIKE :query OR d.race LIKE :query OR d.espece LIKE :query')
                ->setParameter('query', '%'.trim($query).'%');
        }
        if ($status !== null && $status !== '') {
            $builder->andWhere('d.status = :status')->setParameter('status', $status);
        }

        return $builder->getQuery()->getResult();
    }

//    /**
//     * @return Dogs[] Returns an array of Dogs objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Dogs
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
