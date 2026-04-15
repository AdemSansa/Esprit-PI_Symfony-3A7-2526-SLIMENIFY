<?php

namespace App\Controller;

use App\Entity\Therapist;
use App\Form\AdminTherapistType;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/therapists')]
#[IsGranted('ROLE_ADMIN')]
class AdminTherapistController extends AbstractController
{
    #[Route('/', name: 'app_admin_therapist_index', methods: ['GET'])]
    public function index(Request $request, TherapistRepository $therapistRepository, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $searchQuery = $request->query->get('q');
        $specialty = $request->query->get('specialty');
        $mode = $request->query->get('mode');

        $query = $therapistRepository->searchAndSortQuery($searchQuery, $specialty, $mode);

        $therapists = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4 // items per page
        );

        return $this->render('admin_therapist/index.html.twig', [
            'therapists' => $therapists,
            'searchQuery' => $searchQuery,
            'specialty' => $specialty,
            'mode' => $mode,
        ]);
    }

    #[Route('/new', name: 'app_admin_therapist_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        $therapist = new Therapist();
        $form = $this->createForm(AdminTherapistType::class, $therapist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$therapist->getPhotoUrl()) {
                $therapist->setPhotoUrl('default.png');
            }
            if (!$therapist->getDiplomaPath()) {
                $therapist->setDiplomaPath('default_diploma.pdf');
            }
            
            // Generate a default hash for the Therapist
            $hashedPassword = $passwordHasher->hashPassword(new \App\Entity\User(), '12345678');
            $therapist->setPassword($hashedPassword);

            $entityManager->persist($therapist);
            
            // Autocreate the User entity to allow the therapist to login
            $user = new \App\Entity\User();
            $user->setFirstName($therapist->getFirstName());
            $user->setLastName($therapist->getLastName());
            $user->setEmail($therapist->getEmail());
            $user->setPhone($therapist->getPhoneNumber());
            $user->setRole('therapist');
            $user->setPassword($hashedPassword);
            $user->setIsVerified(true);
            $user->setPhotoUrl($therapist->getPhotoUrl());

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_therapist_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_therapist/new.html.twig', [
            'therapist' => $therapist,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_therapist_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Therapist $therapist, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminTherapistType::class, $therapist);
        $form->handleRequest($request);

        // Retain current files if fields are empty
        $originalPhoto = $therapist->getPhotoUrl();
        $originalDiploma = $therapist->getDiplomaPath();

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$therapist->getPhotoUrl()) {
                $therapist->setPhotoUrl($originalPhoto ?: 'default.png');
            }
            if (!$therapist->getDiplomaPath()) {
                $therapist->setDiplomaPath($originalDiploma ?: 'default_diploma.pdf');
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_therapist_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_therapist/edit.html.twig', [
            'therapist' => $therapist,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_therapist_delete', methods: ['POST'])]
    public function delete(Request $request, Therapist $therapist, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$therapist->getId(), $request->request->get('_token'))) {
            $entityManager->remove($therapist);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_therapist_index', [], Response::HTTP_SEE_OTHER);
    }
}
