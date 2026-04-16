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

    /**
     * @return \Doctrine\ORM\Query
     */
    public function searchAndSortQuery(?string $searchQuery, ?string $specialty, ?string $modeFilter = null): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('t');

        if ($searchQuery) {
            $qb->andWhere('t.firstName LIKE :query OR t.lastName LIKE :query')
               ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($specialty && $specialty !== 'all') {
            $qb->andWhere('t.specialization = :spec')
               ->setParameter('spec', $specialty);
        }

        if ($modeFilter && $modeFilter !== 'all') {
            $qb->andWhere('t.consultationType = :mode')
               ->setParameter('mode', $modeFilter);
        }

        $qb->orderBy('t.id', 'ASC');

        return $qb->getQuery();
    }

    /**
     * @return Therapist[]
     */
    public function searchAndSort(?string $searchQuery, ?string $specialty, ?string $modeFilter = null): array
    {
        return $this->searchAndSortQuery($searchQuery, $specialty, $modeFilter)->getResult();
    }
}
