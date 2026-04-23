<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\EventSubscription;
use App\Service\NotificationService;
use App\Form\EventRegistrationType;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Service\GeolocationService;

#[Route('/events/registrations')]
class EventRegistrationController extends AbstractController
{
    #[Route('/list', name: 'app_event_registration_list', methods: ['GET'])]
    public function index(Request $request, RegistrationRepository $registrationRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $queryBuilder = $registrationRepository->createQueryBuilder('r')
                ->orderBy('r.id', 'DESC');
        } elseif ($this->isGranted('ROLE_THERAPIST')) {
            // Filter registrations for events organized by the current therapist
            $queryBuilder = $registrationRepository->createQueryBuilder('r')
                ->join('r.event', 'e')
                ->where('e.organizerId = :organizerId')
                ->setParameter('organizerId', $user->getId())
                ->orderBy('r.id', 'DESC');
        } else {
             // Filter registrations for the patient
             $email = method_exists($user, 'getEmail') ? $user->getEmail() : null;
             $queryBuilder = $registrationRepository->createQueryBuilder('r')
                ->where('r.participantEmail = :email')
                ->setParameter('email', $email)
                ->orderBy('r.id', 'DESC');
        }

        $registrations = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            4 // Items per page
        );

        return $this->render('event_registration/list.html.twig', [
            'registrations' => $registrations,
        ]);
    }

    #[Route('/{id}/register', name: 'app_event_register', methods: ['GET', 'POST'])]
    public function register(Event $event, Request $request, EntityManagerInterface $entityManager, RegistrationRepository $registrationRepository, NotificationService $ns, GeolocationService $geoService): Response
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

        // 📍 Geolocation: Detect Participant Location from IP
        $clientIp = $request->getClientIp();
        $location = $geoService->getLocation($clientIp);
        $registration->setParticipantLocation($location);

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
            
            // 🔔 AUTO-SUBSCRIBING LOGGED-IN USERS TO NOTIFICATIONS
            if ($user) {
                $subRepo = $entityManager->getRepository(EventSubscription::class);
                $existingSub = $subRepo->findOneBy(['user' => $user, 'event' => $event]);
                if (!$existingSub) {
                    $subscription = new EventSubscription();
                    $subscription->setUser($user);
                    $subscription->setEvent($event);
                    $entityManager->persist($subscription);
                }
                
                // 🔔 IMMEDIATE FEEDBACK
                $ns->createUniqueNotification(
                    $user, 
                    $event, 
                    'REGISTERED', 
                    "Registration Confirmed", 
                    "You are successfully registered for '{$event->getTitle()}'. See you there!"
                );
            }

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
        
        // ✨ CUSTOM QR CONTENT: Text message instead of URL
        $qrMessage = "(Registration ID:#{$registration->getId()} \n don't forget to be in the time please)";
        
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=' . urlencode($qrMessage);

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
    public function updateStatus(Request $request, Registration $registration, string $status, EntityManagerInterface $entityManager, NotificationService $ns): Response
    {
        // 🔐 Security: Only ROLE_ADMIN, the Event Organizer, OR the Participant themselves can modify status!
        $isParticipant = ($registration->getParticipantEmail() === $this->getUser()->getEmail());
        
        if (!$this->isGranted('ROLE_ADMIN') && 
            $registration->getEvent()->getOrganizerId() !== $this->getUser()->getId() &&
            !$isParticipant) {
            throw $this->createAccessDeniedException('You can only manage your own registrations.');
        }

        if ($this->isCsrfTokenValid('status'.$registration->getId(), $request->request->get('_token'))) {
            if (in_array($status, ['registered', 'attended', 'cancelled'])) {
                $oldStatus = $registration->getStatus();
                $registration->setStatus($status);
                $entityManager->flush();
                
                // 🔔 Notify on Cancellation
                if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                    $event = $registration->getEvent();
                    $ns->notifyUserByEmail($registration->getParticipantEmail(), $event, 'REG_CANCELLED', 'Registration Cancelled', "Your registration for '{$event->getTitle()}' has been cancelled.");
                    $ns->notifyUserById($event->getOrganizerId(), $event, 'REG_CANCELLED_ORG', 'Attendee Cancelled', "{$registration->getParticipantName()} cancelled their registration for '{$event->getTitle()}'.");
                    $entityManager->flush();
                }

                $this->addFlash('success', 'Status updated.');
            }
        }

        return $this->redirectToRoute('app_event_registration_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_event_registration_delete', methods: ['POST'])]
    public function delete(Request $request, Registration $registration, EntityManagerInterface $entityManager, NotificationService $ns): Response
    {
        // 🔐 Security: Only ROLE_ADMIN, the Event Organizer, OR the Participant themselves can delete!
        $isParticipant = ($registration->getParticipantEmail() === $this->getUser()->getEmail());

        if (!$this->isGranted('ROLE_ADMIN') && 
            $registration->getEvent()->getOrganizerId() !== $this->getUser()->getId() &&
            !$isParticipant) {
            throw $this->createAccessDeniedException('You can only delete your own registrations.');
        }

        if ($this->isCsrfTokenValid('delete'.$registration->getId(), $request->request->get('_token'))) {
            $event = $registration->getEvent();
            $participantEmail = $registration->getParticipantEmail();
            $participantName = $registration->getParticipantName();
            $organizerId = $event->getOrganizerId();

            // 🔔 Notify BEFORE deleting (record needs to exist for event relation)
            $ns->notifyUserByEmail($participantEmail, $event, 'REG_DELETED', 'Registration Deleted', "Your registration for '{$event->getTitle()}' has been removed.");
            $ns->notifyUserById($organizerId, $event, 'REG_DELETED_ORG', 'Registration Removed', "{$participantName} removed their registration for '{$event->getTitle()}'.");
            
            $entityManager->remove($registration);
            $entityManager->flush();
            $this->addFlash('success', 'Registration removed.');
        }

        return $this->redirectToRoute('app_event_registration_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/excel', name: 'app_event_registration_export', methods: ['GET'])]
    public function exportExcel(RegistrationRepository $registrationRepository): StreamedResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        // 🔍 Fetch data based on roles
        if ($this->isGranted('ROLE_ADMIN')) {
            $registrations = $registrationRepository->findAll();
        } elseif ($this->isGranted('ROLE_THERAPIST')) {
            $registrations = $registrationRepository->createQueryBuilder('r')
                ->join('r.event', 'e')
                ->where('e.organizerId = :organizerId')
                ->setParameter('organizerId', $user->getId())
                ->getQuery()
                ->getResult();
        } else {
            throw $this->createAccessDeniedException('You are not authorized to export registrations.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registrations');

        // 🏷️ Set Headers
        $headers = ['ID', 'Event Title', 'Participant Name', 'Email', 'Phone', 'Status', 'Date Registered'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        // 🎨 Style Headers
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // 📝 Fill Data
        $row = 2;
        foreach ($registrations as $reg) {
            $sheet->setCellValue([1, $row], $reg->getId());
            $sheet->setCellValue([2, $row], $reg->getEvent()->getTitle());
            $sheet->setCellValue([3, $row], $reg->getParticipantName());
            $sheet->setCellValue([4, $row], $reg->getParticipantEmail());
            $sheet->setCellValue([5, $row], $reg->getParticipantPhone());
            $sheet->setCellValue([6, $row], ucfirst($reg->getStatus()));
            $sheet->setCellValue([7, $row], $reg->getRegistrationDate()->format('Y-m-d H:i'));
            $row++;
        }

        // 📏 Auto-size columns
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'registrations_export_' . date('Y-m-d') . '.xlsx'
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
