<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/suppliers')]
class SupplierController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SupplierRepository $supplierRepository;

    public function __construct(EntityManagerInterface $entityManager, SupplierRepository $supplierRepository)
    {
        $this->entityManager = $entityManager;
        $this->supplierRepository = $supplierRepository;
    }

    #[Route('', name: 'api_supplier_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $suppliers = $this->supplierRepository->findAll();
        $data = [];

        foreach ($suppliers as $supplier) {
            $data[] = [
                'id' => $supplier->getId(),
                'name' => $supplier->getName(),
                'email' => $supplier->getEmail(),
                'phone' => $supplier->getPhone(),
                'address' => $supplier->getAddress(),
                'city' => $supplier->getCity(),
                'country' => $supplier->getCountry(),
                'status' => $supplier->getStatus(),
                'createdAt' => $supplier->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $supplier->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_supplier_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierRepository->find($id);

        if (!$supplier) {
            return new JsonResponse(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $supplier->getId(),
            'name' => $supplier->getName(),
            'email' => $supplier->getEmail(),
            'phone' => $supplier->getPhone(),
            'address' => $supplier->getAddress(),
            'city' => $supplier->getCity(),
            'country' => $supplier->getCountry(),
            'status' => $supplier->getStatus(),
            'createdAt' => $supplier->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $supplier->getUpdatedAt()?->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('', name: 'api_supplier_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        if (!$content) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $supplier = new Supplier();
        $supplier->setName($content['name'] ?? 'Unknown');
        $supplier->setEmail($content['email'] ?? null);
        $supplier->setPhone($content['phone'] ?? null);
        $supplier->setAddress($content['address'] ?? null);
        $supplier->setCity($content['city'] ?? null);
        $supplier->setCountry($content['country'] ?? null);
        
        if (isset($content['status'])) {
            $supplier->setStatus($content['status']);
        }

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Supplier created successfully', 'id' => $supplier->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_supplier_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = $this->supplierRepository->find($id);

        if (!$supplier) {
            return new JsonResponse(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }

        $content = json_decode($request->getContent(), true);
        if (!$content) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($content['name'])) $supplier->setName($content['name']);
        if (array_key_exists('email', $content)) $supplier->setEmail($content['email']);
        if (array_key_exists('phone', $content)) $supplier->setPhone($content['phone']);
        if (array_key_exists('address', $content)) $supplier->setAddress($content['address']);
        if (array_key_exists('city', $content)) $supplier->setCity($content['city']);
        if (array_key_exists('country', $content)) $supplier->setCountry($content['country']);
        if (isset($content['status'])) $supplier->setStatus($content['status']);

        $supplier->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Supplier updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_supplier_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $supplier = $this->supplierRepository->find($id);

        if (!$supplier) {
            return new JsonResponse(['error' => 'Supplier not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($supplier);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Supplier deleted successfully'], Response::HTTP_OK);
    }
}
