<?php

namespace App\Repository;

use App\Entity\Dealership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dealership>
 */
class DealershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dealership::class);
    }
    public function findNearest(float $lat, float $lon, int $limit = 10, int $offset = 0): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
        SELECT d.*, (
            6371 * acos(
                cos(radians(:lat)) * cos(radians(d.latitude)) *
                cos(radians(d.longitude) - radians(:lon)) +
                sin(radians(:lat)) * sin(radians(d.latitude))
            )
        ) AS distance
        FROM dealership d
        ORDER BY distance ASC
        LIMIT :limit OFFSET :offset
    ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('lat', $lat);
        $stmt->bindValue('lon', $lon);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);

        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }


    //    /**
    //     * @return Dealership[] Returns an array of Dealership objects
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

    //    public function findOneBySomeField($value): ?Dealership
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
