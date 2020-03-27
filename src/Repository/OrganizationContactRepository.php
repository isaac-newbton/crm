<?php

namespace App\Repository;

use App\Doctrine\UuidEncoder;
use App\Entity\OrganizationContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method OrganizationContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganizationContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganizationContact[]    findAll()
 * @method OrganizationContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationContactRepository extends ServiceEntityRepository
{
    use RepositoryUuidFinderTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationContact::class);
        $this->uuidEncoder = new UuidEncoder();
    }

    // /**
    //  * @return OrganizationContact[] Returns an array of OrganizationContact objects
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
    public function findOneBySomeField($value): ?OrganizationContact
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
