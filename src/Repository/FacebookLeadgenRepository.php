<?php

namespace App\Repository;

use App\Entity\FacebookLeadgen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FacebookLeadgen|null find($id, $lockMode = null, $lockVersion = null)
 * @method FacebookLeadgen|null findOneBy(array $criteria, array $orderBy = null)
 * @method FacebookLeadgen[]    findAll()
 * @method FacebookLeadgen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FacebookLeadgenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FacebookLeadgen::class);
    }

    // /**
    //  * @return FacebookLeadgen[] Returns an array of FacebookLeadgen objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FacebookLeadgen
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
