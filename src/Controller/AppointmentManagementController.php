<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Note;
use App\Entity\Therapist;
use App\Repository\AppointmentRepository;
use App\Repository\AvailabilityRepository;
use App\Repository\NoteRepository;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\GeminiAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/appointments', name: 'app_appointments_')]
class AppointmentManagementController extends AbstractController
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private TherapistRepository $therapistRepository,
        private AvailabilityRepository $availabilityRepository,
        private NoteRepository $noteRepository,
        private EntityManagerInterface $entityManager,
        private GeminiAIService $aiService
    ) {
    }

    #[Route('/calendar', name: 'calendar', methods: ['GET'])]
    public function calendar(Request $request): Response
    {
        $therapists = $this->therapistRepository->findBy(['status' => 'ACTIVE'], ['id' => 'ASC'], 100);

        $selectedTherapistId = $request->query->getInt('therapist_id');
        $selectedTherapist = null;

        if ($this->isGranted('ROLE_THERAPIST') && !$selectedTherapistId) {
            $selectedTherapist = $this->resolveTherapistForCurrentUser();
            $selectedTherapistId = $selectedTherapist?->getId() ?? 0;
        } elseif ($selectedTherapistId) {
            $selectedTherapist = $this->therapistRepository->find($selectedTherapistId);
        }

        return $this->render('appointment/calendar.html.twig', [
            'therapists' => $therapists,
            'selected_therapist_id' => $selectedTherapistId,
            'is_therapist' => $this->isGranted('ROLE_THERAPIST'),
            'selected_therapist' => $selectedTherapist,
        ]);
    }

    #[Route('/events', name: 'events', methods: ['GET'])]
    public function events(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $therapist = $this->resolveTherapistFromRequest($request);
        if ($therapist === null) {
            if ($this->isGranted('ROLE_PATIENT') && !$this->isGranted('ROLE_THERAPIST')) {
                $user = $this->getUser();
                if ($user instanceof \App\Entity\User) {
                    $appointments = $this->appointmentRepository->findByPatient((int) $user->getId());
                } else {
                    return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
                }
            } else {
                return $this->json(['error' => 'Therapist not found'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $appointments = $this->appointmentRepository->findByTherapist((int) $therapist->getId());
        }
        $events = [];
        foreach ($appointments as $appointment) {
            if (!$this->canAccessAppointment($appointment)) {
                continue;
            }

            $date = $appointment->getAppointmentDate()->format('Y-m-d');
            $start = $appointment->getStartTime()->format('H:i:s');
            $end = $appointment->getEndTime()->format('H:i:s');
            $status = strtolower((string) $appointment->getStatus());
            
            $title = sprintf('%s • %s', ucfirst((string) $appointment->getType()), ucfirst($status ?: 'pending'));
            if ($this->isGranted('ROLE_THERAPIST')) {
                $title .= sprintf(' (%s %s)', $appointment->getPatient()->getFirstName(), $appointment->getPatient()->getLastName());
            } else {
                $title .= sprintf(' (Dr. %s)', $appointment->getTherapist()->getLastName());
            }

            $events[] = [
                'id' => $appointment->getId(),
                'title' => $title,
                'start' => $date . 'T' . $start,
                'end' => $date . 'T' . $end,
                'backgroundColor' => $this->statusColor($status),
                'borderColor' => $this->statusColor($status),
                'extendedProps' => [
                    'status' => $status,
                    'type' => $appointment->getType(),
                    'detailUrl' => $this->generateUrl('app_appointments_detail', ['id' => $appointment->getId()]),
                    'canEditTime' => $this->isGranted('ROLE_THERAPIST'),
                ],
            ];
        }

        $response = $this->json($events);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    #[Route('/business-hours', name: 'business_hours', methods: ['GET'])]
    public function businessHours(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $therapist = $this->resolveTherapistFromRequest($request);
        if ($therapist === null) {
            return $this->json(['error' => 'Therapist not found'], Response::HTTP_BAD_REQUEST);
        }

        $availabilities = $this->availabilityRepository->findByTherapistId((int) $therapist->getId());
        $dayMap = [
            'SUNDAY' => 0,
            'MONDAY' => 1,
            'TUESDAY' => 2,
            'WEDNESDAY' => 3,
            'THURSDAY' => 4,
            'FRIDAY' => 5,
            'SATURDAY' => 6,
        ];

        $businessHours = [];
        $exceptions = [];
        foreach ($availabilities as $availability) {
            if ($availability->getSpecificDate() !== null) {
                if (!$availability->isAvailable()) {
                    $exceptions[] = [
                        'date' => $availability->getSpecificDate()->format('Y-m-d'),
                        'startTime' => $availability->getStartTime()->format('H:i:s'),
                        'endTime' => $availability->getEndTime()->format('H:i:s'),
                    ];
                }
                continue;
            }
            if (!$availability->isAvailable()) {
                continue;
            }
            $businessHours[] = [
                'daysOfWeek' => [$dayMap[$availability->getDay()] ?? 1],
                'startTime' => $availability->getStartTime()->format('H:i:s'),
                'endTime' => $availability->getEndTime()->format('H:i:s'),
            ];
        }

        $response = $this->json([
            'businessHours' => $businessHours,
            'exceptions' => $exceptions,
            'consultationType' => strtoupper((string) ($therapist->getConsultationType() ?: 'BOTH')),
            'therapistName' => trim($therapist->getFirstName() . ' ' . $therapist->getLastName()),
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Expires', '0');
        return $response;
    }

    #[Route('/available-therapists', name: 'available_therapists', methods: ['GET'])]
    public function availableTherapists(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $dateStr = $request->query->getString('date');
        $startStr = $request->query->getString('start_time');
        $type = strtolower($request->query->getString('type'));

        if (!$dateStr || !$startStr) {
            return $this->json(['error' => 'Missing date or time'], Response::HTTP_BAD_REQUEST);
        }

        $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
        $start = \DateTime::createFromFormat('H:i', $startStr);
        if (!$date || !$start) {
            return $this->json(['error' => 'Invalid date/time format'], Response::HTTP_BAD_REQUEST);
        }
        $end = (clone $start)->modify('+60 minutes');

        $patient = $this->getUser();
        if ($patient instanceof \App\Entity\User) {
            if ($this->appointmentRepository->hasOverlapForPatient((int) $patient->getId(), $date, $start, $end)) {
                return $this->json(['error' => 'You already have another appointment booked at this exact time!'], Response::HTTP_CONFLICT);
            }
        }

        $activeTherapists = $this->therapistRepository->findBy(['status' => 'ACTIVE'], ['id' => 'ASC'], 100);
        $available = [];

        foreach ($activeTherapists as $therapist) {
            if ($type && !$this->isAppointmentTypeAllowed($therapist, $type)) {
                continue;
            }
            if (!$this->isSlotInsideBusinessHours($therapist, $date, $start, $end)) {
                continue;
            }
            if ($this->appointmentRepository->hasOverlapForTherapist((int) $therapist->getId(), $date, $start, $end)) {
                continue;
            }
            $available[] = [
                'id' => $therapist->getId(),
                'name' => trim($therapist->getFirstName() . ' ' . $therapist->getLastName()),
                'consultationType' => $therapist->getConsultationType(),
            ];
        }

        return $this->json(['therapists' => $available]);
    }

    #[Route('/book', name: 'book', methods: ['POST'])]
    public function book(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $data = json_decode($request->getContent(), true) ?? [];
        $therapist = $this->therapistRepository->find((int) ($data['therapist_id'] ?? 0));
        if ($therapist === null) {
            return $this->json(['error' => 'Therapist not found'], Response::HTTP_BAD_REQUEST);
        }

        $patient = $this->getUser();
        if (!$patient instanceof \App\Entity\User) {
            return $this->json(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $date = \DateTime::createFromFormat('Y-m-d', (string) ($data['date'] ?? ''));
        $start = \DateTime::createFromFormat('H:i', (string) ($data['start_time'] ?? ''));
        $type = strtolower((string) ($data['type'] ?? ''));
        if (!$date || !$start) {
            return $this->json(['error' => 'Invalid date/time range'], Response::HTTP_BAD_REQUEST);
        }
        $end = (clone $start)->modify('+60 minutes');

        $fullStart = (clone $date)->setTime((int) $start->format('H'), (int) $start->format('i'));
        if ($fullStart < new \DateTime()) {
            return $this->json(['error' => 'You cannot book appointments in the past.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isAppointmentTypeAllowed($therapist, $type)) {
            return $this->json(['error' => 'Appointment type not allowed for this therapist'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isSlotInsideBusinessHours($therapist, $date, $start, $end)) {
            return $this->json(['error' => 'Selected slot is outside business hours or blocked by exception'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->appointmentRepository->hasOverlapForTherapist((int) $therapist->getId(), $date, $start, $end)) {
            return $this->json(['error' => 'Selected slot overlaps an existing appointment'], Response::HTTP_CONFLICT);
        }

        if ($this->appointmentRepository->hasOverlapForPatient((int) $patient->getId(), $date, $start, $end)) {
            return $this->json(['error' => 'You already have an appointment booked with another therapist at this time.'], Response::HTTP_CONFLICT);
        }

        $appointment = new Appointment();
        $appointment->setTherapist($therapist);
        $appointment->setPatient($patient);
        $appointment->setAppointmentDate($date);
        $appointment->setStartTime($start);
        $appointment->setEndTime($end);
        $appointment->setType($type);
        $appointment->setStatus('pending');
        $this->appointmentRepository->save($appointment);

        if ($type === 'video') {
            $appointment->setJitsiUrl($this->generateJitsiUrl($appointment));
            $this->appointmentRepository->save($appointment);
        }

        return $this->json(['ok' => true, 'id' => $appointment->getId()], Response::HTTP_CREATED);
    }

    #[Route('/{id}/move', name: 'move', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function move(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $appointment = $this->appointmentRepository->find($id);
        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            return $this->json(['error' => 'Appointment not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $date = \DateTime::createFromFormat('Y-m-d', (string) ($data['date'] ?? ''));
        $start = \DateTime::createFromFormat('H:i', (string) ($data['start_time'] ?? ''));
        if (!$date || !$start) {
            return $this->json(['error' => 'Invalid date/time range'], Response::HTTP_BAD_REQUEST);
        }
        $end = (clone $start)->modify('+60 minutes');

        if ($appointment->getStatus() === 'completed') {
            return $this->json(['error' => 'Completed appointments cannot be moved.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isSlotInsideBusinessHours($appointment->getTherapist(), $date, $start, $end)) {
            return $this->json(['error' => 'Slot is outside business hours or blocked by exception'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->appointmentRepository->hasOverlapForTherapist((int) $appointment->getTherapist()->getId(), $date, $start, $end, $appointment->getId())) {
            return $this->json(['error' => 'Slot overlaps another appointment'], Response::HTTP_CONFLICT);
        }

        if ($this->appointmentRepository->hasOverlapForPatient((int) $appointment->getPatient()->getId(), $date, $start, $end, $appointment->getId())) {
            return $this->json(['error' => 'You already have an appointment booked with another therapist at this time.'], Response::HTTP_CONFLICT);
        }

        $appointment->setAppointmentDate($date);
        $appointment->setStartTime($start);
        $appointment->setEndTime($end);
        $this->appointmentRepository->save($appointment);

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/status', name: 'status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function status(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $appointment = $this->appointmentRepository->find($id);
        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            throw $this->createNotFoundException('Appointment not found.');
        }

        $status = strtolower((string) $request->request->get('status', ''));
        if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
        }

        $currentStatus = strtolower((string) ($appointment->getStatus() ?: 'pending'));
        if ($currentStatus === 'completed') {
            $this->addFlash('error', 'Cannot change status of a completed appointment.');
            return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
        }

        if ($status === 'completed') {
            $now = new \DateTime();
            $appointTimeStr = $appointment->getAppointmentDate()->format('Y-m-d') . ' ' . $appointment->getStartTime()->format('H:i:s');
            $appointDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $appointTimeStr);
            if ($now < $appointDateTime) {
                $this->addFlash('error', 'Cannot mark appointment as completed before its scheduled time.');
                return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
            }
        }

        if (!$this->isGranted('ROLE_THERAPIST')) {
            if ($status !== 'cancelled') {
                $this->addFlash('error', 'Patients can only cancel appointments.');
                return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
            }
        } elseif (!$this->isValidStatusProgression($currentStatus, $status)) {
            $this->addFlash('error', 'Status can only move forward: pending -> confirmed -> completed -> cancelled.');
            return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
        }

        if ($status === 'cancelled') {
            $this->entityManager->remove($appointment);
            $this->entityManager->flush();
            $this->addFlash('success', 'Appointment cancelled and deleted.');
            return $this->redirectToRoute('app_appointments_history');
        }

        $appointment->setStatus($status);
        $this->appointmentRepository->save($appointment);
        $this->addFlash('success', 'Appointment status updated.');

        return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(Request $request, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $appointments = [];
        if ($this->isGranted('ROLE_THERAPIST')) {
            $therapist = $this->resolveTherapistForCurrentUser();
            if ($therapist !== null) {
                $appointments = $this->appointmentRepository->findByTherapist((int) $therapist->getId());
            }
        } else {
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $appointments = $this->appointmentRepository->findByPatient((int) $user->getId());
            }
        }

        // Filter by status
        $statusFilter = strtolower($request->query->getString('status'));
        if ($statusFilter && $statusFilter !== 'all') {
            $appointments = array_filter($appointments, function ($a) use ($statusFilter) {
                return strtolower($a->getStatus() ?? 'pending') === $statusFilter;
            });
        }

        // Custom Sorting
        $now = new \DateTime();
        $nowDateOnly = (clone $now)->setTime(0, 0);

        usort($appointments, function ($a, $b) use ($nowDateOnly) {
            $aIsFuture = $a->getAppointmentDate() >= $nowDateOnly;
            $bIsFuture = $b->getAppointmentDate() >= $nowDateOnly;

            if ($aIsFuture && !$bIsFuture)
                return -1;
            if (!$aIsFuture && $bIsFuture)
                return 1;

            if ($aIsFuture) {
                // Future: closest time first (ascending)
                return $a->getAppointmentDate() <=> $b->getAppointmentDate() ?: $a->getStartTime() <=> $b->getStartTime();
            } else {
                // Past: most recent first (descending)
                return $b->getAppointmentDate() <=> $a->getAppointmentDate() ?: $b->getStartTime() <=> $a->getStartTime();
            }
        });

        // Paginate the array
        $pagination = $paginator->paginate(
            $appointments,
            $request->query->getInt('page', 1),
            4 // 2 items per page for testing
        );

        return $this->render('appointment/history.html.twig', [
            'appointments' => $pagination,
            'is_therapist' => $this->isGranted('ROLE_THERAPIST'),
            'current_filter' => $statusFilter,
        ]);
    }

    #[Route('/detail-readonly/{id}', name: 'detail_readonly', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detailReadonly(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $appointment = $this->appointmentRepository->find($id);
        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            throw $this->createNotFoundException('Appointment not found.');
        }

        return $this->render('appointment/detail.html.twig', [
            'appointment' => $appointment,
            'notes' => $this->noteRepository->findBy(['appointment' => $appointment], ['createdAt' => 'DESC'], 50),
            'can_manage_status' => false,
            'can_manage_notes' => false,
            'next_statuses' => [],
            'readonly' => true,
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $appointment = $this->appointmentRepository->find($id);
        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            throw $this->createNotFoundException('Appointment not found.');
        }

        $currentStatus = strtolower((string) ($appointment->getStatus() ?: 'pending'));
        $canManageStatus = $this->isGranted('ROLE_THERAPIST') || ($this->isGranted('ROLE_PATIENT') && $currentStatus !== 'completed');
        $nextStatuses = [];
        if ($this->isGranted('ROLE_THERAPIST')) {
            $nextStatuses = $this->getNextStatuses($currentStatus);
        } elseif ($this->isGranted('ROLE_PATIENT') && $currentStatus !== 'completed') {
            $nextStatuses = ['cancelled'];
        }

        $jitsiUrl = null;
        if (strtolower((string) $appointment->getType()) === 'video') {
            $jitsiUrl = $appointment->getJitsiUrl() ?: $this->generateJitsiUrl($appointment); // fallback for existing appointments
        }

        return $this->render('appointment/detail.html.twig', [
            'appointment' => $appointment,
            'notes' => $this->noteRepository->findBy(['appointment' => $appointment], ['createdAt' => 'DESC'], 50),
            'can_manage_status' => $canManageStatus,
            'can_manage_notes' => $this->isGranted('ROLE_THERAPIST') && $currentStatus === 'completed',
            'next_statuses' => $nextStatuses,
            'readonly' => false,
            'jitsi_url' => $jitsiUrl,
        ]);
    }

    #[Route('/{id}/summarize', name: 'summarize', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function summarize(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $appointment = $this->appointmentRepository->find($id);

        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            return $this->json(['error' => 'Appointment not found'], Response::HTTP_NOT_FOUND);
        }

        $notes = $this->noteRepository->findBy(['appointment' => $appointment]);
        if (empty($notes)) {
            return $this->json(['error' => 'No notes found for this appointment.'], Response::HTTP_BAD_REQUEST);
        }

        $noteTexts = array_map(fn($n) => $n->getContent(), $notes);

        // Performance: We can use fastcgi_finish_request here if we wanted to push results via WebSockets,
        // but for a simple AJAX call, we just return the result.
        $summary = $this->aiService->summarizeNotes($noteTexts);

        return $this->json(['summary' => $summary]);
    }

    private function generateJitsiUrl(Appointment $appointment): string
    {
        // Unique room name based on ID and a secret salt
        $roomName = 'PsychologySession_' . $appointment->getId() . '_' . substr(md5($appointment->getCreatedAt()->format('YmdHis')), 0, 8);
        return 'https://meet.jit.si/' . $roomName;
    }

    #[Route('/{id}/notes', name: 'add_note', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addNote(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $appointment = $this->appointmentRepository->find($id);
        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            throw $this->createNotFoundException('Appointment not found.');
        }
        if (strtolower((string) $appointment->getStatus()) !== 'completed') {
            $this->addFlash('error', 'You can only add notes when the appointment status is completed.');
            return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
        }

        $therapist = $this->resolveTherapistForCurrentUser();
        if ($therapist === null || $therapist->getId() !== $appointment->getTherapist()->getId()) {
            throw $this->createAccessDeniedException('Not allowed.');
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content === '') {
            $this->addFlash('error', 'Note content cannot be empty.');
            return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
        }

        $note = new Note();
        $note->setAppointment($appointment);
        $note->setTherapist($therapist);
        $note->setContent($content);
        $note->setMood(($m = $request->request->get('mood')) !== null ? (string) $m : null);
        $this->noteRepository->save($note);

        $this->addFlash('success', 'Note added successfully.');
        return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
    }

    #[Route('/notes/{noteId}/edit', name: 'edit_note', requirements: ['noteId' => '\d+'], methods: ['POST'])]
    public function editNote(int $noteId, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $note = $this->noteRepository->find($noteId);
        if ($note === null || !$this->canAccessAppointment($note->getAppointment())) {
            throw $this->createNotFoundException('Note not found.');
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content !== '') {
            $note->setContent($content);
        }
        $note->setMood(($m = $request->request->get('mood')) !== null ? (string) $m : null);
        $this->noteRepository->save($note);
        $this->addFlash('success', 'Note updated.');

        return $this->redirectToRoute('app_appointments_detail', ['id' => $note->getAppointment()->getId()]);
    }

    #[Route('/notes/{noteId}/delete', name: 'delete_note', requirements: ['noteId' => '\d+'], methods: ['POST'])]
    public function deleteNote(int $noteId): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $note = $this->noteRepository->find($noteId);
        if ($note === null || !$this->canAccessAppointment($note->getAppointment())) {
            throw $this->createNotFoundException('Note not found.');
        }

        $appointmentId = $note->getAppointment()->getId();
        $this->entityManager->remove($note);
        $this->entityManager->flush();

        $this->addFlash('success', 'Note deleted.');
        return $this->redirectToRoute('app_appointments_detail', ['id' => $appointmentId]);
    }

    #[Route('/{id}/patient-mood', name: 'patient_mood', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function patientMood(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');
        $appointment = $this->appointmentRepository->find($id);
        if ($appointment === null || !$this->canAccessAppointment($appointment)) {
            throw $this->createNotFoundException('Appointment not found.');
        }

        if (strtolower((string) $appointment->getStatus()) !== 'completed') {
            $this->addFlash('error', 'Mood can only be provided after the appointment is completed.');
            return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getId() !== $appointment->getPatient()->getId()) {
            throw $this->createAccessDeniedException('Only the patient can update their mood.');
        }

        $mood = trim((string) $request->request->get('patient_mood', ''));
        if ($mood !== '') {
            $appointment->setPatientMood($mood);
            $this->appointmentRepository->save($appointment);
            $this->addFlash('success', 'Your mood has been recorded.');
        }

        return $this->redirectToRoute('app_appointments_detail', ['id' => $id]);
    }

    private function resolveTherapistFromRequest(Request $request): ?Therapist
    {
        $id = $request->query->getInt('therapist_id');
        if ($id > 0) {
            return $this->therapistRepository->find($id);
        }

        if ($this->isGranted('ROLE_THERAPIST')) {
            return $this->resolveTherapistForCurrentUser();
        }

        return null;
    }

    private function resolveTherapistForCurrentUser(): ?Therapist
    {
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User && $user->getEmail()) {
            return $this->therapistRepository->findOneByEmail((string) $user->getEmail());
        }

        return null;
    }

    private function isSlotInsideBusinessHours(Therapist $therapist, \DateTimeInterface $date, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        $availabilityRows = $this->availabilityRepository->findByTherapistId((int) $therapist->getId());
        $day = strtoupper($date->format('l'));
        $hasCoveringBusinessHour = false;

        foreach ($availabilityRows as $row) {
            if ($row->getSpecificDate() === null && $row->isAvailable() && $row->getDay() === $day) {
                if (
                    $row->getStartTime()->format('H:i:s') <= $start->format('H:i:s')
                    && $row->getEndTime()->format('H:i:s') >= $end->format('H:i:s')
                ) {
                    $hasCoveringBusinessHour = true;
                    break;
                }
            }
        }

        if (!$hasCoveringBusinessHour) {
            return false;
        }

        foreach ($availabilityRows as $row) {
            if ($row->getSpecificDate() === null || $row->isAvailable()) {
                continue;
            }

            if ($row->getSpecificDate()->format('Y-m-d') !== $date->format('Y-m-d')) {
                continue;
            }

            $rowStart = $row->getStartTime()->format('H:i:s');
            $rowEnd = $row->getEndTime()->format('H:i:s');
            if ($rowStart < $end->format('H:i:s') && $rowEnd > $start->format('H:i:s')) {
                return false;
            }
        }

        return true;
    }

    private function isAppointmentTypeAllowed(Therapist $therapist, string $requestedType): bool
    {
        $consultationType = strtoupper((string) ($therapist->getConsultationType() ?: 'BOTH'));
        $allowed = match ($consultationType) {
            'ONLINE' => ['video'],
            'IN_PERSON' => ['presentiel'],
            default => ['video', 'presentiel'],
        };

        return in_array($requestedType, $allowed, true);
    }

    private function canAccessAppointment(Appointment $appointment): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_THERAPIST')) {
            $therapist = $this->resolveTherapistForCurrentUser();
            return $therapist !== null && $therapist->getId() === $appointment->getTherapist()->getId();
        }

        $user = $this->getUser();
        return $user instanceof \App\Entity\User && $user->getId() === $appointment->getPatient()->getId();
    }



    private function statusColor(string $status): string
    {
        return match ($status) {
            'confirmed' => '#22c55e',
            'completed' => '#748764',
            default => '#eab308',
        };
    }

    private function isValidStatusProgression(string $current, string $next): bool
    {
        return in_array($next, $this->getNextStatuses($current), true);
    }

    /** @return array<int, string> */
    private function getNextStatuses(string $current): array
    {
        return match ($current) {
            'pending' => ['confirmed', 'completed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            default => [],
        };
    }
}
