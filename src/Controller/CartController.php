<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
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
            'total' => $total,
            'suggestions' => empty($cart) ? $productRepository->findBy([], null, 3) : []
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST', 'GET'])]
    public function add(Product $product, Request $request): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);
        $id = $product->getId();

        // 🔒 Duplicate check: product already in cart
        if (!empty($cart[$id])) {
            $this->addFlash('warning', sprintf('"%s" is already in your cart. Use the cart to update the quantity.', $product->getName()));
            return $this->redirectToRoute('app_product_index');
        }

        $quantity = (int) $request->get('quantity', 1);
        if ($quantity < 1) $quantity = 1;

        $cart[$id] = $quantity;
        $session->set('cart', $cart);

        $this->addFlash('success', sprintf('"%s" has been added to your cart.', $product->getName()));

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

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['GET'])]
    public function checkout(ProductRepository $productRepository): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

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

        return $this->render('cart/checkout.html.twig', [
            'items' => $cartData,
            'total' => $total
        ]);
    }

    #[Route('/confirm', name: 'app_cart_confirm', methods: ['POST'])]
    public function confirm(Request $request, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Get form data
        $address = $request->request->get('address');
        $phone = $request->request->get('phone');
        $paymentMethod = $request->request->get('payment_method');

        if (!$address || !$phone || !$paymentMethod) {
            $this->addFlash('error', 'Please fill in all delivery information.');
            return $this->redirectToRoute('app_cart_checkout');
        }

        $commande = new Commande();
        $commande->setUser($this->getUser());
        $commande->setShippingAddress($address);
        $commande->setContactPhone($phone);
        $commande->setPaymentMethod($paymentMethod);
        $commande->setStatus('en_attente');

        $totalAmount = 0;
        $itemsDetails = [];

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                // 🛑 Stock Check
                if ($product->getStockQuantity() < $quantity) {
                    $this->addFlash('error', sprintf('Sorry, only %d units of "%s" are available in stock.', $product->getStockQuantity(), $product->getName()));
                    return $this->redirectToRoute('app_cart_index');
                }

                $price = $product->getPrice();
                $itemTotal = $price * $quantity;
                $totalAmount += $itemTotal;

                // 📉 Decrement Stock
                $product->setStockQuantity($product->getStockQuantity() - $quantity);

                $itemsDetails[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total_price' => $itemTotal
                ];
            }
        }

        $commande->setTotalAmount($totalAmount);
        $commande->setItemsDetails($itemsDetails);

        $em->persist($commande);
        $em->flush();

        // Clear the cart
        $session->set('cart', []);

        $this->addFlash('success', "🎊 Congratulations! Your order (Total: $totalAmount TND) has been confirmed successfully.");

        return $this->redirectToRoute('app_product_index');
    }
}
