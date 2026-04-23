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
    public function findMostLikedQuery()
    {
    return $this->createQueryBuilder('b')
        ->leftJoin('b.likes', 'l')
        ->addSelect('COUNT(l.id) AS HIDDEN likesCount')
        ->groupBy('b.id')
        ->orderBy('likesCount', 'DESC')
        ->getQuery();
    }
        public function findByCategory($category)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getBlogsCountByCategory(): array
    {
        return $this->createQueryBuilder('b')
            ->select('c.name as label, COUNT(b.id) as value')
            ->join('b.category', 'c')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function getBlogsEvolution(): array
    {
        $data = $this->createQueryBuilder('b')
            ->select('b.createdAt')
            ->orderBy('b.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $evolution = [];
        foreach ($data as $item) {
            $date = $item['createdAt']->format('Y-m-d');
            if (!isset($evolution[$date])) {
                $evolution[$date] = 0;
            }
            $evolution[$date]++;
        }

        $result = [];
        foreach ($evolution as $date => $count) {
            $result[] = ['date' => $date, 'count' => $count];
        }

        return $result;
    }

    public function getPopularCategoryByInteractions(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c.name as name')
            ->addSelect('COUNT(DISTINCT l.id) + COUNT(DISTINCT com.id) + COUNT(DISTINCT f.id) as interactions')
            ->from(\App\Entity\Category::class, 'c')
            ->leftJoin('c.blogs', 'b')
            ->leftJoin('b.likes', 'l')
            ->leftJoin('b.comments', 'com')
            ->leftJoin('b.favorites', 'f')
            ->groupBy('c.id')
            ->orderBy('interactions', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}