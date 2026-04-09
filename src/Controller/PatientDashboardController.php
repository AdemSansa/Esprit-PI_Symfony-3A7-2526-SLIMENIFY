<?php

namespace App\Controller;

use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Symfony\Component\HttpFoundation\Request;

#[Route('/patient', name: 'app_patient_')]
#[IsGranted('ROLE_PATIENT')]
class PatientDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request, TherapistRepository $therapistRepository): Response
    {
        $searchQuery = $request->query->get('q');
        $specialty = $request->query->get('specialty', 'all');

        $therapists = $therapistRepository->searchAndSort($searchQuery, $specialty);

        return $this->render('patient_portal/dashboard.html.twig', [
            'therapists' => $therapists,
            'searchQuery' => $searchQuery,
            'specialty' => $specialty,
        ]);
    }
}
