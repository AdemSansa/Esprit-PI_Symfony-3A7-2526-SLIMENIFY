<?php

namespace App\Controller;

use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/therapist', name: 'app_therapist_')]
#[IsGranted('ROLE_THERAPIST')]
class TherapistDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(TherapistRepository $therapistRepo): Response
    {
        $user = $this->getUser();
        $therapist = $therapistRepo->findOneBy(['email' => $user->getEmail()]);
        
        return $this->render('therapist_portal/dashboard.html.twig', [
            'therapist' => $therapist,
            'therapist_id' => $therapist ? $therapist->getId() : null,
        ]);
    }

    #[Route('/directory', name: 'directory')]
    public function directory(TherapistRepository $therapistRepository): Response
    {
        // Read-only list of therapists for colleagues!
        return $this->render('therapist_portal/directory.html.twig', [
            'therapists' => $therapistRepository->findAll(),
        ]);
    }
}
