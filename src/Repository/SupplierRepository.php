<?php

namespace App\Repository;

use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Supplier>
 */
class SupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    /**
     * Server-side filtered and sorted supplier query.
     */
    /** @return \Doctrine\ORM\Query */
    public function findFiltered(
        ?string $search = null,
        ?string $status = null,
        ?string $sort = 'newest'
    ): \Doctrine\ORM\Query {
        $qb = $this->createQueryBuilder('s');

        // Search by name, email, or company/city
        if (!empty($search)) {
            $searchTerm = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(s.name) LIKE :term OR LOWER(s.email) LIKE :term OR LOWER(s.city) LIKE :term')
               ->setParameter('term', $searchTerm);
        }

        // Filter by status
        if (!empty($status) && $status !== 'all') {
            $qb->andWhere('s.status = :selectedStatus')
               ->setParameter('selectedStatus', $status);
        }

        // Sorting
        switch ($sort) {
            case 'name-asc':
                $qb->orderBy('s.name', 'ASC');
                break;
            case 'name-desc':
                $qb->orderBy('s.name', 'DESC');
                break;
            case 'city-asc':
                $qb->orderBy('s.city', 'ASC');
                break;
            case 'newest':
            default:
                $qb->orderBy('s.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery();
    }

    public function save(Supplier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Supplier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
