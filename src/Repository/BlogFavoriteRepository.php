<?php

namespace App\Repository;

use App\Entity\BlogFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BlogFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogFavorite::class);
    }

    public function findOneByUserAndBlog($user, $blog)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.blog = :blog')
            ->setParameter('user', $user)
            ->setParameter('blog', $blog)
            ->getQuery()
            ->getOneOrNullResult();
    }
}