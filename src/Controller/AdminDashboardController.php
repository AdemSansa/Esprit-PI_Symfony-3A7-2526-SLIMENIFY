<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/dashboard')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        SupplierRepository $supplierRepository,
        CommandeRepository $commandeRepository
    ): Response {
        // Product Stats
        $totalProducts = $productRepository->count([]);
        $lowStockProducts = $productRepository->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.stockQuantity < :limit')
            ->setParameter('limit', 5)
            ->getQuery()
            ->getSingleScalarResult();

        // Supplier Stats
        $totalSuppliers = $supplierRepository->count([]);
        
        // Orders & Revenue
        $orders = $commandeRepository->findAll();
        $totalRevenue = 0;
        $orderCount = count($orders);
        
        // Unify Status Breakdown (Removing language duplicates)
        $statusBreakdown = [
            'pending' => 0,
            'paid' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];
        
        $statusMap = [
            'en_attente' => 'pending',
            'pending' => 'pending',
            'payée' => 'paid',
            'paid' => 'paid',
            'livrée' => 'delivered',
            'delivered' => 'delivered',
            'annulée' => 'cancelled',
            'cancelled' => 'cancelled',
            'validated' => 'paid', // Mapping Validated to Paid for clarity
        ];

        $supplierRevenue = [];
        $allProductIdsInOrders = [];

        foreach ($orders as $order) {
            $rawStatus = strtolower(trim($order->getStatus()));
            $unifiedStatus = $statusMap[$rawStatus] ?? 'other';
            
            // Increment status count
            if ($unifiedStatus !== 'other') {
                $statusBreakdown[$unifiedStatus]++;
            }

            // ONLY COUNT REVENUE FOR DELIVERED ORDERS
            if ($unifiedStatus === 'delivered') {
                $totalRevenue += $order->getTotalAmount();

                // Track revenue per supplier for the "Best Supplier" stat
                foreach ($order->getItemsDetails() as $item) {
                    $productId = $item['id'] ?? null;
                    $itemTotal = $item['total_price'] ?? 0;
                    if ($productId) {
                        $allProductIdsInOrders[$productId] = ($allProductIdsInOrders[$productId] ?? 0) + $itemTotal;
                    }
                }
            }
        }

        // Identify Best Supplier
        $bestSupplier = null;
        $bestSupplierRevenue = 0;

        if (!empty($allProductIdsInOrders)) {
            $products = $productRepository->findBy(['id' => array_keys($allProductIdsInOrders)]);
            $tempSupplierMap = [];
            foreach ($products as $product) {
                if ($product->getSupplier()) {
                    $sName = $product->getSupplier()->getName();
                    $rev = $allProductIdsInOrders[$product->getId()];
                    $tempSupplierMap[$sName] = ($tempSupplierMap[$sName] ?? 0) + $rev;
                }
            }
            
            if (!empty($tempSupplierMap)) {
                arsort($tempSupplierMap);
                $bestSupplier = key($tempSupplierMap);
                $bestSupplierRevenue = current($tempSupplierMap);
            }
        }

        $recentOrders = $commandeRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/dashboard/index.html.twig', [
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'total_suppliers' => $totalSuppliers,
            'total_revenue' => $totalRevenue,
            'order_count' => $orderCount,
            'status_breakdown' => $statusBreakdown,
            'recent_orders' => $recentOrders,
            'best_supplier' => $bestSupplier,
            'best_supplier_revenue' => $bestSupplierRevenue,
        ]);
    }
}
