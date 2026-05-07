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
            $rawStatus = strtolower(trim((string) $order->getStatus()));
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

        // --- CHART DATA ---
        // 1. Supplier Revenue Map for Pie Chart
        $supplierRevenueMap = [];
        if (!empty($allProductIdsInOrders)) {
            $allProducts = $productRepository->findBy(['id' => array_keys($allProductIdsInOrders)]);
            foreach ($allProducts as $product) {
                if ($product->getSupplier()) {
                    $sName = $product->getSupplier()->getName();
                    $rev = $allProductIdsInOrders[$product->getId()];
                    $supplierRevenueMap[$sName] = ($supplierRevenueMap[$sName] ?? 0) + $rev;
                }
            }
            arsort($supplierRevenueMap);
        }

        // 2. Monthly Revenue Line Chart (last 6 months, delivered orders only)
        /** @var array<string, int> $monthlyRevenue */
        $monthlyRevenue = [];
        $monthlyLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("first day of -$i months");
            $monthlyLabels[] = $date->format('M Y');
            $monthlyRevenue[$date->format('Y-m')] = 0;
        }

        foreach ($orders as $order) {
            $rawStatus = strtolower(trim((string) $order->getStatus()));
            $unifiedStatus = $statusMap[$rawStatus] ?? 'other';
            if ($unifiedStatus === 'delivered' && $order->getCreatedAt()) {
                $monthKey = $order->getCreatedAt()->format('Y-m');
                if (isset($monthlyRevenue[$monthKey])) {
                    $monthlyRevenue[$monthKey] += $order->getTotalAmount();
                }
            }
        }

        $monthlyRevenueValues = [];
        foreach ($monthlyRevenue as $amount) {
            $monthlyRevenueValues[] = $amount;
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'total_products'          => $totalProducts,
            'low_stock_products'      => $lowStockProducts,
            'total_suppliers'         => $totalSuppliers,
            'total_revenue'           => $totalRevenue,
            'order_count'             => $orderCount,
            'status_breakdown'        => $statusBreakdown,
            'recent_orders'           => $recentOrders,
            'best_supplier'           => $bestSupplier,
            'best_supplier_revenue'   => $bestSupplierRevenue,
            'supplier_revenue_map'    => $supplierRevenueMap,
            'monthly_revenue_labels'  => $monthlyLabels,
            'monthly_revenue_values'  => $monthlyRevenueValues,
        ]);

    }
}
