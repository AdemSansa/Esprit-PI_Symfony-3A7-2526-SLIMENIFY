<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function save(Question $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Question $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Question[]
     */
    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.questionText LIKE :query')
            ->orWhere('q.category LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, mixed>
     */
    public function findForManagement(?string $query, ?string $category, int $page, int $perPage = 8): array
    {
        $qb = $this->createQueryBuilder('q');

        if ($query) {
            $qb
                ->andWhere('q.questionText LIKE :query OR q.category LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb
                ->andWhere('q.category = :category')
                ->setParameter('category', $category);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));

        $questions = $qb
            ->orderBy('q.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'questions' => $questions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
    }
}
