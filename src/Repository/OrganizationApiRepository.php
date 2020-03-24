<?php

namespace App\Repository;

use App\Entity\OrganizationApi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method OrganizationApi|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganizationApi|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganizationApi[]    findAll()
 * @method OrganizationApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationApiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationApi::class);
    }

    // /**
    //  * @return OrganizationApi[] Returns an array of OrganizationApi objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OrganizationApi
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
