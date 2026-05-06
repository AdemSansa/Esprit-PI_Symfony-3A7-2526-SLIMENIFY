<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    #[Route('', name: 'app_admin_orders_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        return $this->render('admin/order/index.html.twig', [
            'orders' => $commandeRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/status', name: 'app_admin_orders_update_status', methods: ['POST'])]
    public function updateStatus(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        $newStatus = $request->request->getString('status');
        if ($newStatus) {
            $commande->setStatus($newStatus);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            $this->addFlash('success', 'Order #' . $commande->getId() . ' status updated.');
        }

        return $this->redirectToRoute('app_admin_orders_index');
    }

    #[Route('/{id}/delete', name: 'app_admin_orders_delete', methods: ['POST'])]
    public function delete(Commande $commande, EntityManagerInterface $em): Response
    {
        $em->remove($commande);
        $em->flush();
        $this->addFlash('success', 'Order deleted successfully.');

        return $this->redirectToRoute('app_admin_orders_index');
    }
}
