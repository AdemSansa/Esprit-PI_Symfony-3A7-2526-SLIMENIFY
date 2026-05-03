<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;
    private SupplierRepository $supplierRepository;

    public function __construct(
        EntityManagerInterface $entityManager, 
        ProductRepository $productRepository,
        SupplierRepository $supplierRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->supplierRepository = $supplierRepository;
    }

    #[Route('', name: 'api_product_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'stockQuantity' => $product->getStockQuantity(),
                'category' => $product->getCategory(),
                'expirationDate' => $product->getExpirationDate()?->format('Y-m-d'),
                'supplier_id' => $product->getSupplier()?->getId(),
                'photoUrl' => $product->getPhotoUrl(),
                'status' => $product->getStatus(),
                'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'stockQuantity' => $product->getStockQuantity(),
            'category' => $product->getCategory(),
            'expirationDate' => $product->getExpirationDate()?->format('Y-m-d'),
            'supplier_id' => $product->getSupplier()?->getId(),
            'photoUrl' => $product->getPhotoUrl(),
            'status' => $product->getStatus(),
            'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if (!$content) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($content['name'] ?? 'Unknown');
        $product->setDescription($content['description'] ?? null);
        
        if (isset($content['price'])) {
            $product->setPrice($content['price']);
        }
        
        if (isset($content['stockQuantity'])) {
            $product->setStockQuantity((int) $content['stockQuantity']);
        }

        $product->setCategory($content['category'] ?? null);

        if (!empty($content['expirationDate'])) {
            try {
                $product->setExpirationDate(new \DateTime($content['expirationDate']));
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        if (!empty($content['supplier_id'])) {
            $supplier = $this->supplierRepository->find($content['supplier_id']);
            if ($supplier) {
                $product->setSupplier($supplier);
            }
        }

        $product->setPhotoUrl($content['photoUrl'] ?? null);

        if (isset($content['status'])) {
            $product->setStatus($content['status']);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Product created successfully', 'id' => $product->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $content = json_decode($request->getContent(), true);
        if (!$content) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($content['name'])) $product->setName($content['name']);
        if (array_key_exists('description', $content)) $product->setDescription($content['description']);
        if (isset($content['price'])) $product->setPrice($content['price']);
        if (isset($content['stockQuantity'])) $product->setStockQuantity((int) $content['stockQuantity']);
        if (array_key_exists('category', $content)) $product->setCategory($content['category']);
        
        if (array_key_exists('expirationDate', $content)) {
            if ($content['expirationDate']) {
                try {
                    $product->setExpirationDate(new \DateTime($content['expirationDate']));
                } catch (\Exception $e) {}
            } else {
                $product->setExpirationDate(null);
            }
        }

        if (array_key_exists('supplier_id', $content)) {
            if ($content['supplier_id']) {
                $supplier = $this->supplierRepository->find($content['supplier_id']);
                if ($supplier) {
                    $product->setSupplier($supplier);
                }
            } else {
                $product->setSupplier(null);
            }
        }

        if (array_key_exists('photoUrl', $content)) $product->setPhotoUrl($content['photoUrl']);
        if (isset($content['status'])) $product->setStatus($content['status']);

        $product->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Product updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
