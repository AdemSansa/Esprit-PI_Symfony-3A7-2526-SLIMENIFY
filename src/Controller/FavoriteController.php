<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteController extends AbstractController
{
    #[Route('/favorites', name: 'app_favorite_index')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $favoritesIds = $session->get('favorites', []);
        
        $products = [];
        if (!empty($favoritesIds)) {
            $products = $productRepository->findBy(['id' => $favoritesIds]);
        }

        return $this->render('favorite/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/favorites/toggle/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggle(int $id, SessionInterface $session): Response
    {
        $favorites = $session->get('favorites', []);
        
        // Remove if exists
        if (($key = array_search($id, $favorites)) !== false) {
            unset($favorites[$key]);
            $isFavorite = false;
        } else {
            // Add if doesn't exist
            $favorites[] = $id;
            $isFavorite = true;
        }
        
        $session->set('favorites', array_values($favorites));
        
        return $this->json(['success' => true, 'isFavorite' => $isFavorite]);
    }
}
