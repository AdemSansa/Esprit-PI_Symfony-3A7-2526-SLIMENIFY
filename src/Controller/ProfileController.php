<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $photoFile */
            $photoFile = $form->get('photoUrl')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                        $newFilename
                    );
                    $user->setPhotoUrl('/uploads/photos/'.$newFilename);
                } catch (\Exception $e) {
                    // Do not overwrite existing photo URL if upload fails, or set fallback
                }
            }

            $entityManager->persist($user);
            
            // Sync with Therapist entity if user is a therapist
            if ($user->getRole() === 'therapist') {
                $therapist = $entityManager->getRepository(\App\Entity\Therapist::class)->findOneBy(['email' => $user->getEmail()]);
                if ($therapist) {
                    $therapist->setFirstName((string)$user->getFirstName());
                    if ($user->getLastName()) $therapist->setLastName((string)$user->getLastName());
                    $therapist->setEmail((string)$user->getEmail());
                    if ($user->getPhone()) $therapist->setPhoneNumber((string)$user->getPhone());
                    if ($user->getPhotoUrl()) $therapist->setPhotoUrl((string)$user->getPhotoUrl());
                    $entityManager->persist($therapist);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
