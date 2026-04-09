<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Server-side filtered, searched, and sorted product query.
     */
    public function findFiltered(
        ?string $search = null,
        ?string $category = null,
        ?string $sort = 'newest',
        ?float $priceMin = null,
        ?float $priceMax = null
    ): array {
        $qb = $this->createQueryBuilder('p');

        // Search by name or description
        if (!empty($search)) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search)')
               ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Filter by category
        if (!empty($category) && $category !== 'all') {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        // Filter by price range
        if ($priceMin !== null && $priceMin > 0) {
            $qb->andWhere('p.price >= :priceMin')
               ->setParameter('priceMin', $priceMin);
        }

        if ($priceMax !== null && $priceMax > 0 && $priceMax < 2000) {
            $qb->andWhere('p.price <= :priceMax')
               ->setParameter('priceMax', $priceMax);
        }

        // Sorting
        switch ($sort) {
            case 'price-low':
                $qb->orderBy('p.price', 'ASC');
                break;
            case 'price-high':
                $qb->orderBy('p.price', 'DESC');
                break;
            case 'name-asc':
                $qb->orderBy('p.name', 'ASC');
                break;
            case 'name-desc':
                $qb->orderBy('p.name', 'DESC');
                break;
            case 'newest':
            default:
                $qb->orderBy('p.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
