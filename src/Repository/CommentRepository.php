<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /** @return Comment[] */
    public function findByBlog(int $blogId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.blog = :blog')
            ->setParameter('blog', $blogId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}