<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
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
    ): \Doctrine\ORM\Query {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('(CASE WHEN p.stockQuantity > 0 THEN 1 ELSE 0 END) AS HIDDEN hasStock')
            ->leftJoin('p.supplier', 's')
            ->addOrderBy('hasStock', 'DESC');

        // Search by name, description or category
        if (!empty($search)) {
            $searchTerm = '%' . strtolower(trim($search)) . '%';
            $qb->andWhere('LOWER(p.name) LIKE :term OR LOWER(p.description) LIKE :term OR LOWER(p.category) LIKE :term')
               ->setParameter('term', $searchTerm);
        }

        // Filter by category (dropdown)
        if (!empty($category) && $category !== 'all') {
            $qb->andWhere('p.category = :selectedCategory')
               ->setParameter('selectedCategory', $category);
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
                $qb->addOrderBy('p.price', 'ASC');
                break;
            case 'price-high':
                $qb->addOrderBy('p.price', 'DESC');
                break;
            case 'name-asc':
                $qb->addOrderBy('p.name', 'ASC');
                break;
            case 'name-desc':
                $qb->addOrderBy('p.name', 'DESC');
                break;
            case 'newest':
            default:
                $qb->addOrderBy('p.createdAt', 'DESC');
                break;
        }

        return $qb->getQuery();
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
