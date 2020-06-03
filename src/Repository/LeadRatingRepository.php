<?php

namespace App\Repository;

use App\Entity\LeadRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LeadRating|null find($id, $lockMode = null, $lockVersion = null)
 * @method LeadRating|null findOneBy(array $criteria, array $orderBy = null)
 * @method LeadRating[]    findAll()
 * @method LeadRating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeadRating::class);
    }

    // /**
    //  * @return LeadRating[] Returns an array of LeadRating objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LeadRating
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
