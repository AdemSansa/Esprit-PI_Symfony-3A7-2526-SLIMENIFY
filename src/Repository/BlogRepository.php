<?php

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    public function findAllOrdered()
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function searchByTitle(?string $query): array
    {
    return $this->createQueryBuilder('b')
        ->where('b.title LIKE :q')
        ->setParameter('q', '%' . $query . '%')
        ->orderBy('b.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
    }
}