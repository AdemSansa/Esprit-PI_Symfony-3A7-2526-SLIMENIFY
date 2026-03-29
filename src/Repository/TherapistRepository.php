<?php

namespace App\Repository;

use App\Entity\Therapist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TherapistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Therapist::class);
    }

    public function save(Therapist $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(Therapist $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findActive(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findBySpecialization(string $specialization): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.specialization LIKE :spec')
            ->setParameter('spec', '%' . $specialization . '%')
            ->getQuery()->getResult();
    }

    public function findByConsultationType(string $type): array
    {
        return $this->findBy(['consultationType' => $type, 'status' => 'ACTIVE']);
    }
}
