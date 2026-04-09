<?php

namespace App\Repository;

use App\Entity\Availability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    public function save(Availability $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Availability $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTherapistId(int $therapistId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.therapist = :therapistId')
            ->setParameter('therapistId', $therapistId)
            ->orderBy('a.specificDate', 'ASC')
            ->addOrderBy('a.day', 'ASC')
            ->addOrderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
