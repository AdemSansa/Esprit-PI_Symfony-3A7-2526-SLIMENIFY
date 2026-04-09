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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $registrations = $registrationRepository->findAll();
        } elseif ($this->isGranted('ROLE_THERAPIST')) {
            // Filter registrations for events organized by the current therapist
            $registrations = $registrationRepository->createQueryBuilder('r')
                ->join('r.event', 'e')
                ->where('e.organizerId = :organizerId')
                ->setParameter('organizerId', $user->getId())
                ->getQuery()
                ->getResult();
        } else {
             // Filter registrations for the patient
             $email = method_exists($user, 'getEmail') ? $user->getEmail() : null;
             $registrations = $email ? $registrationRepository->findBy(['participantEmail' => $email]) : [];
        }

        return $this->render('event_registration/list.html.twig', [
            'registrations' => $registrations,
        ]);
    }

    #[Route('/{id}/register', name: 'app_event_register', methods: ['GET', 'POST'])]
    public function register(Event $event, Request $request, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository): Response
    {
        // 🔐 Security: Pure Therapists cannot register for events, but Admins CAN!
        if ($this->isGranted('ROLE_THERAPIST') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('As a Therapist, you cannot register as a participant. Please use a Patient account.');
        }

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
            
            // 🛑 CHECK FOR DUPLICATE REGISTRATION (ONE PER EVENT PER EMAIL)
            $existing = $registrationRepository->findOneBy([
                'participantEmail' => $registration->getParticipantEmail(),
                'event' => $event
            ]);

            if ($existing) {
                $this->addFlash('error', 'You are already registered for this event!');
                return $this->redirectToRoute('app_event_index');
            }

            // Generate QR Code data
            $qrData = "EVENT:".$event->getId()."|NAME:".$registration->getParticipantName()."|EMAIL:".$registration->getParticipantEmail();
            $registration->setQrCode($qrData);
            
            $entityManager->persist($registration);
            $entityManager->flush();

            $this->addFlash('success', 'Registration successful! Here is your ticket.');
            return $this->redirectToRoute('app_event_register_success', ['id' => $registration->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_registration/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/success', name: 'app_event_register_success', methods: ['GET'])]
    public function success(Registration $registration, Request $request): Response
    {
        // --- UNIVERSAL DYNAMIC HOST DETECTION ---
        // This will automatically detect if you are on localhost, your Wi-Fi IP, 
        // or a public tunnel like serveo.net/localhost.run.
        // It always uses the SAME host you are currently browsing on!
        
        $currentHost = $request->getSchemeAndHttpHost();
        $publicBaseUrl = $currentHost . '/ticket_pass_full.html';
        
        $params = [
            'name' => $registration->getParticipantName(),
            'event' => $registration->getEvent()->getTitle(),
            'id' => $registration->getId(),
            'date' => $registration->getEvent()->getDateStart()->format('d.m.Y'),
            'time' => $registration->getEvent()->getDateStart()->format('H:i')
        ];
        
        // Final Dynamically-Detected URL
        $ticketUrl = $publicBaseUrl . '?' . http_build_query($params);
        
        // Simple, clean QR code that works on whichever link you are using!
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=' . urlencode($ticketUrl);

        return $this->render('event_registration/success.html.twig', [
            'registration' => $registration,
            'qrCodeUrl' => $qrCodeUrl
        ]);
    }

    #[Route('/{id}/ticket', name: 'app_event_registration_ticket', methods: ['GET'])]
    public function ticket(Registration $registration): Response
    {
        // Publicly accessible digital ticket view
        return $this->render('event_registration/ticket.html.twig', [
            'registration' => $registration,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_event_registration_status', methods: ['POST'])]
    public function updateStatus(Request $request, Registration $registration, string $status, EntityManagerInterface $entityManager): Response
    {
        // 🔐 Security: Only ROLE_ADMIN or the Event Organizer can modify status!
        if (!$this->isGranted('ROLE_ADMIN') && $registration->getEvent()->getOrganizerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only manage registrations for your own events.');
        }

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
        // 🔐 Security: Only ROLE_ADMIN or the Event Organizer can delete registrations!
        if (!$this->isGranted('ROLE_ADMIN') && $registration->getEvent()->getOrganizerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('You can only delete registrations for your own events.');
        }

        if ($this->isCsrfTokenValid('delete'.$registration->getId(), $request->request->get('_token'))) {
            $entityManager->remove($registration);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_registration_list', [], Response::HTTP_SEE_OTHER);
    }
}
