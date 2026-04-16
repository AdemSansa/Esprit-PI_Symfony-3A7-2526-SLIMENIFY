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
        $latestSuppliers = $supplierRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // Payment/Order Stats
        $orders = $commandeRepository->findAll();
        $totalRevenue = 0;
        $orderCount = count($orders);
        $statusBreakdown = [
            'en_attente' => 0,
            'payée' => 0,
            'livrée' => 0,
            'annulée' => 0,
        ];

        foreach ($orders as $order) {
            $totalRevenue += $order->getTotalAmount();
            $status = $order->getStatus();
            if (isset($statusBreakdown[$status])) {
                $statusBreakdown[$status]++;
            } else {
                $statusBreakdown[$status] = ($statusBreakdown[$status] ?? 0) + 1;
            }
        }

        $recentOrders = $commandeRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/dashboard/index.html.twig', [
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'total_suppliers' => $totalSuppliers,
            'latest_suppliers' => $latestSuppliers,
            'total_revenue' => $totalRevenue,
            'order_count' => $orderCount,
            'status_breakdown' => $statusBreakdown,
            'recent_orders' => $recentOrders,
        ]);
    }
}
