<?php

namespace App\Controller;

use App\Repository\TherapistRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient', name: 'app_patient_')]
#[IsGranted('ROLE_PATIENT')]
class PatientDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request, TherapistRepository $therapistRepository, PaginatorInterface $paginator): Response
    {
        $searchQuery = ($v = $request->query->get('q')) !== null ? (string) $v : null;
        $specialty = ($v = $request->query->get('specialty', 'all')) !== null ? (string) $v : null;

        $query = $therapistRepository->searchAndSort($searchQuery, $specialty, null, 99);

        $therapists = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4, // Limit per page
            ['distinct' => false]
        );

        return $this->render('patient_portal/dashboard.html.twig', [
            'therapists' => $therapists,
            'searchQuery' => $searchQuery,
            'specialty' => $specialty,
        ]);
    }
}
