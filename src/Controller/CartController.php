<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_PATIENT')]
class CartController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('', name: 'app_cart_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        $cartData = [];
        $total = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $itemTotal = $product->getPrice() * $quantity;
                $cartData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'total' => $itemTotal
                ];
                $total += $itemTotal;
            }
        }

        return $this->render('cart/index.html.twig', [
            'items' => $cartData,
            'total' => $total
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST', 'GET'])]
    public function add(Product $product, Request $request): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $id = $product->getId();

        $quantity = (int) $request->get('quantity', 1);
        if ($quantity < 1) $quantity = 1;

        if (!empty($cart[$id])) {
            $cart[$id] += $quantity;
        } else {
            $cart[$id] = $quantity;
        }

        $session->set('cart', $cart);

        $this->addFlash('success', sprintf('added %d unit(s) of "%s" to your cart.', $quantity, $product->getName()));

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        $quantity = (int) $request->get('quantity');
        
        if ($quantity > 0) {
            $cart[$id] = $quantity;
        } else {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        $this->addFlash('success', 'Cart updated successfully.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['GET', 'POST'])]
    public function remove(int $id): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);
        $this->addFlash('success', 'Item removed from cart.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['GET', 'POST'])]
    public function clear(): Response
    {
        $session = $this->requestStack->getSession();
        $session->set('cart', []);

        $this->addFlash('success', 'Your cart has been cleared.');

        return $this->redirectToRoute('app_cart_index');
    }
}
