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
     public function getReviewStats(): array
    {
    $qb = $this->createQueryBuilder('r');

    // Total reviews
    $total = $qb->select('COUNT(r.id)')
        ->getQuery()
        ->getSingleScalarResult();

    // Reviews answered
    $answered = $this->createQueryBuilder('r')
        ->select('COUNT(DISTINCT r.id)')
        ->leftJoin('r.replies', 'rep')
        ->where('rep.id IS NOT NULL')
        ->getQuery()
        ->getSingleScalarResult();

    $unanswered = $total - $answered;

    $answeredPercent = $total > 0 ? round(($answered / $total) * 100, 2) : 0;
    $unansweredPercent = $total > 0 ? round(($unanswered / $total) * 100, 2) : 0;

    return [
        'total' => $total,
        'answered' => $answered,
        'unanswered' => $unanswered,
        'answeredPercent' => $answeredPercent,
        'unansweredPercent' => $unansweredPercent,
    ];
    }
}
