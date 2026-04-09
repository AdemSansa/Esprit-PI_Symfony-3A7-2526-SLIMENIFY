<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Review $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findAllSortedByDate(string $order = 'DESC'): array
    {
    return $this->createQueryBuilder('r')
        ->orderBy('r.createdAt', $order)
        ->getQuery()
        ->getResult();
    }
    public function findBySearchAndSort(?string $search, string $order = 'DESC'): array
    {
    $qb = $this->createQueryBuilder('r');

    if ($search) {
        $qb->where('r.content LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    $qb->orderBy('r.createdAt', $order);

    return $qb->getQuery()->getResult();
    }
}
