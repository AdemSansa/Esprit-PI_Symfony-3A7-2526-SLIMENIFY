<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function save(Quiz $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush)
            $this->getEntityManager()->flush();
    }

    public function remove(Quiz $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush)
            $this->getEntityManager()->flush();
    }

    public function findActive(): array
    {
        return $this->findBy(['active' => Quiz::STATUS_ACTIVE]);
    }

    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.title LIKE :query')
            ->orWhere('q.description LIKE :query')
            ->orWhere('q.category LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
    public function findByAuthor(int $author): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getResult();
    }

    public function findByAuthorAndSearchQuery(int $author, string $query): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.author = :author')
            ->andWhere('q.title LIKE :query OR q.description LIKE :query OR q.category LIKE :query')
            ->setParameter('author', $author)
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    public function countTotalQuizzes(): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
