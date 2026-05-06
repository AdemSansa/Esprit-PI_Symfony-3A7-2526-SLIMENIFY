<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/supplier')]
#[IsGranted('ROLE_ADMIN')]
class SupplierCrudController extends AbstractController
{
    #[Route('', name: 'app_supplier_index', methods: ['GET'])]
    public function index(Request $request, SupplierRepository $supplierRepository, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $search = (string) $request->query->get('search', '');
        $status = (string) $request->query->get('status', 'all');
        $sortBy = (string) $request->query->get('sortBy', 'newest');

        $suppliersQuery = $supplierRepository->findFiltered($search, $status, $sortBy);
        
        $suppliers = $paginator->paginate(
            $suppliersQuery,
            $request->query->getInt('page', 1),
            5 // 5 suppliers per page
        );

        return $this->render('supplier/index.html.twig', [
            'suppliers' => $suppliers,
            'search' => $search,
            'status' => $status,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route('/new', name: 'app_supplier_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $supplier = new Supplier();
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($supplier);
            $entityManager->flush();

            $this->addFlash('success', 'The supplier has been registered successfully.');

            return $this->redirectToRoute('app_supplier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('supplier/new.html.twig', [
            'supplier' => $supplier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SupplierType::class, $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $supplier->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'The supplier information has been updated successfully.');

            return $this->redirectToRoute('app_supplier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('supplier/edit.html.twig', [
            'supplier' => $supplier,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_supplier_delete', methods: ['POST'])]
    public function delete(Request $request, Supplier $supplier, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$supplier->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($supplier);
            $entityManager->flush();
            $this->addFlash('success', 'The supplier has been removed from the system.');
        }

        return $this->redirectToRoute('app_supplier_index', [], Response::HTTP_SEE_OTHER);
    }
}
