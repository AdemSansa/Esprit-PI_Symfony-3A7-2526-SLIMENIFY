<?php

namespace App\Repository;

use App\Entity\BlogFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogFavorite>
 */
class BlogFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogFavorite::class);
    }

    /**
     * @return BlogFavorite|null
     */
    public function findOneByUserAndBlog(\App\Entity\User $user, \App\Entity\Blog $blog): ?BlogFavorite
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