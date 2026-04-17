<?php

namespace App\Repository;

use App\Entity\QuizResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuizResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizResult::class);
    }

    public function save(QuizResult $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuizResult $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getGlobalStats(): array
    {
        $qb = $this->createQueryBuilder('qr');
        $result = $qb->select('COUNT(qr.id) as total_attempts, AVG(qr.score) as average_score')
            ->getQuery()
            ->getSingleResult();
        
        return [
            'total_attempts' => (int) $result['total_attempts'],
            'average_score' => $result['average_score'] !== null ? round((float) $result['average_score'], 1) : 0,
        ];
    }

    public function getTopAttemptedQuizzes(int $limit = 5): array
    {
        return $this->createQueryBuilder('qr')
            ->select('q.title, COUNT(qr.id) as attempt_count')
            ->join('qr.quiz', 'q')
            ->groupBy('q.id')
            ->orderBy('attempt_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getScoresPerQuiz(int $limit = 5): array
    {
        return $this->createQueryBuilder('qr')
            ->select('q.title, AVG(qr.score) as avg_score')
            ->join('qr.quiz', 'q')
            ->groupBy('q.id')
            ->orderBy('avg_score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getCompletionTrends(int $days = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT DATE(taken_at) as date, AVG(score) as avg_score, COUNT(id) as completions
            FROM quiz_results
            WHERE taken_at >= :date
            GROUP BY DATE(taken_at)
            ORDER BY date ASC
        ';
        $date = (new \DateTime("-{$days} days"))->format('Y-m-d');
        // Explicitly use connection executeQuery which correctly parses parameter arrays
        $result = $conn->executeQuery($sql, ['date' => $date]);
        return $result->fetchAllAssociative();
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return $this->createQueryBuilder('qr')
            ->select('qr.takenAt as takenAt, qr.score as score, u.firstName as firstName, u.lastName as lastName, q.title as quiz_title')
            ->join('qr.user', 'u')
            ->join('qr.quiz', 'q')
            ->orderBy('qr.takenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
