<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Registration;
use App\Form\EventRegistrationType;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/events/registrations')]
class EventRegistrationController extends AbstractController
{
    #[Route('/list', name: 'app_event_registration_list', methods: ['GET'])]
    public function index(RegistrationRepository $registrationRepository): Response
    {
        // Add auth check here if needed (e.g. ROLE_THERAPIST)
        $this->denyAccessUnlessGranted('ROLE_USER'); // Default fallback

        $registrations = $registrationRepository->findAll();

        return $this->render('event_registration/list.html.twig', [
            'registrations' => $registrations,
        ]);
    }

    #[Route('/{id}/register', name: 'app_event_register', methods: ['GET', 'POST'])]
    public function register(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
        $registration = new Registration();
        $registration->setEvent($event);
        
        $user = $this->getUser();
        if ($user) {
            // Auto fill
            if (method_exists($user, 'getFirstName') && method_exists($user, 'getLastName')) {
                $registration->setParticipantName($user->getFirstName() . ' ' . $user->getLastName());
            } elseif (method_exists($user, 'getUserIdentifier')) {
                $registration->setParticipantName($user->getUserIdentifier());
            }
            if (method_exists($user, 'getEmail')) {
                $registration->setParticipantEmail($user->getEmail());
            }
            if (method_exists($user, 'getPhone')) {
                $registration->setParticipantPhone($user->getPhone());
            }
        }

        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Generate QR Code data
            $qrData = "EVENT:".$event->getId()."|NAME:".$registration->getParticipantName()."|EMAIL:".$registration->getParticipantEmail();
            $registration->setQrCode($qrData);
            
            $entityManager->persist($registration);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_register_success', ['id' => $registration->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_registration/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/success', name: 'app_event_register_success', methods: ['GET'])]
    public function success(Registration $registration): Response
    {
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode('For more Details Visit https://www.psychologies.com/ - Participant: ' . $registration->getParticipantName());

        return $this->render('event_registration/success.html.twig', [
            'registration' => $registration,
            'qrCodeUrl' => $qrCodeUrl
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_event_registration_status', methods: ['POST'])]
    public function updateStatus(Request $request, Registration $registration, string $status, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('status'.$registration->getId(), $request->request->get('_token'))) {
            if (in_array($status, ['registered', 'attended', 'cancelled'])) {
                $registration->setStatus($status);
                $entityManager->flush();
                $this->addFlash('success', 'Status updated.');
            }
        }

        return $this->redirectToRoute('app_event_registration_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_event_registration_delete', methods: ['POST'])]
    public function delete(Request $request, Registration $registration, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$registration->getId(), $request->request->get('_token'))) {
            $entityManager->remove($registration);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_registration_list', [], Response::HTTP_SEE_OTHER);
    }
}
