<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function searchAndSortQuery(?string $searchQuery, ?string $roleFilter): \Doctrine\ORM\Query
    {
        $qb = $this->createQueryBuilder('u');

        if ($searchQuery) {
            $qb->andWhere('u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query')
               ->setParameter('query', '%' . $searchQuery . '%');
        }

        if ($roleFilter) {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $roleFilter);
        }

        $qb->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function searchAndSort(?string $searchQuery, ?string $roleFilter): array
    {
        return $this->searchAndSortQuery($searchQuery, $roleFilter)->getResult();
    }
}
