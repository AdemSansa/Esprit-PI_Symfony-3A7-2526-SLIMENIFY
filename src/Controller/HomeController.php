<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_THERAPIST')) {
            return $this->redirectToRoute('app_therapist_dashboard');
        }

        if ($this->isGranted('ROLE_PATIENT')) {
            return $this->redirectToRoute('app_patient_dashboard');
        }

        return $this->render('home/index.html.twig');
    }

}
