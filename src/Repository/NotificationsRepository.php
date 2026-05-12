<?php

namespace App\Repository;

use App\Entity\Notifications;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notifications::class);
    }

    /**
     * @return Notifications[] Returns an array of Notifications for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :val')
            ->setParameter('val', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int Returns the number of unread notifications for a user
     */
    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('count(n.id)')
            ->andWhere('n.user = :val')
            ->andWhere('n.isRead = :read')
            ->setParameter('val', $user)
            ->setParameter('read', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}