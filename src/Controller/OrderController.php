<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('', name: 'app_orders_index')]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $orders = $commandeRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }
}
