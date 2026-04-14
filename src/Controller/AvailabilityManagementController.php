<?php

namespace App\Controller;

use App\Entity\Availability;
use App\Repository\AvailabilityRepository;
use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/availability', name: 'app_availability_')]
class AvailabilityManagementController extends AbstractController
{
    public function __construct(
        private AvailabilityRepository $availabilityRepository,
        private TherapistRepository $therapistRepository
    ) {}

    #[Route('', name: 'manage', methods: ['GET'])]
    public function manage(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $therapist = $this->resolveTherapistForCurrentUser($request);
        if ($therapist === null) {
            throw $this->createNotFoundException('Therapist profile was not found for this user.');
        }

        $availabilities = $this->availabilityRepository->findByTherapistId($therapist->getId());
        $businessHours = array_filter($availabilities, static fn (Availability $a) => $a->getSpecificDate() === null && $a->isAvailable());
        $exceptions = array_filter($availabilities, static fn (Availability $a) => $a->getSpecificDate() !== null && !$a->isAvailable());

        return $this->render('availability/manage.html.twig', [
            'therapist' => $therapist,
            'business_hours' => $businessHours,
            'exceptions' => $exceptions,
        ]);
    }

    #[Route('/business-hours', name: 'add_business_hours', methods: ['POST'])]
    public function addBusinessHours(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $therapist = $this->resolveTherapistForCurrentUser($request);
        if ($therapist === null) {
            throw $this->createNotFoundException('Therapist profile was not found for this user.');
        }

        $day = strtoupper((string) $request->request->get('day', ''));
        $start = (string) $request->request->get('start_time', '');
        $end = (string) $request->request->get('end_time', '');
        if (!in_array($day, ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'], true)) {
            $this->addFlash('error', 'Please select a valid weekday.');
            return $this->redirectToRoute('app_availability_manage');
        }

        $startTime = \DateTime::createFromFormat('H:i', $start) ?: \DateTime::createFromFormat('H:i:s', $start);
        $endTime = \DateTime::createFromFormat('H:i', $end) ?: \DateTime::createFromFormat('H:i:s', $end);
        if (!$startTime || !$endTime || $endTime <= $startTime) {
            $this->addFlash('error', 'Please provide a valid time range.');
            return $this->redirectToRoute('app_availability_manage');
        }

        $availability = new Availability();
        $availability->setTherapist($therapist);
        $availability->setDay($day);
        $availability->setStartTime($startTime);
        $availability->setEndTime($endTime);
        $availability->setIsAvailable(true);
        $availability->setSpecificDate(null);
        $this->availabilityRepository->save($availability);

        $this->addFlash('success', 'Business hour added successfully.');
        return $this->redirectToRoute('app_availability_manage');
    }

    #[Route('/exceptions', name: 'add_exception', methods: ['POST'])]
    public function addException(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $therapist = $this->resolveTherapistForCurrentUser($request);
        if ($therapist === null) {
            throw $this->createNotFoundException('Therapist profile was not found for this user.');
        }

        $date = (string) $request->request->get('specific_date', '');
        $start = (string) $request->request->get('start_time', '');
        $end = (string) $request->request->get('end_time', '');
        $specificDate = \DateTime::createFromFormat('Y-m-d', $date);
        $startTime = \DateTime::createFromFormat('H:i', $start) ?: \DateTime::createFromFormat('H:i:s', $start);
        $endTime = \DateTime::createFromFormat('H:i', $end) ?: \DateTime::createFromFormat('H:i:s', $end);

        if (!$specificDate || !$startTime || !$endTime || $endTime <= $startTime) {
            $this->addFlash('error', 'Please provide a valid exception date/time range.');
            return $this->redirectToRoute('app_availability_manage');
        }

        if ($specificDate < new \DateTime('today')) {
            $this->addFlash('error', 'You cannot add exceptions in the past.');
            return $this->redirectToRoute('app_availability_manage');
        }

        $availabilityRows = $this->availabilityRepository->findByTherapistId($therapist->getId());
        $day = strtoupper($specificDate->format('l'));
        $isWithinBusinessHours = false;
        foreach ($availabilityRows as $row) {
            if ($row->getSpecificDate() === null && $row->isAvailable() && $row->getDay() === $day) {
                if ($row->getStartTime()->format('H:i:s') <= $startTime->format('H:i:s')
                    && $row->getEndTime()->format('H:i:s') >= $endTime->format('H:i:s')) {
                    $isWithinBusinessHours = true;
                    break;
                }
            }
        }

        if (!$isWithinBusinessHours) {
            $this->addFlash('error', 'Exceptions must be within your recurring business hours for that day.');
            return $this->redirectToRoute('app_availability_manage');
        }

        foreach ($availabilityRows as $row) {
            if ($row->getSpecificDate() && $row->getSpecificDate()->format('Y-m-d') === $specificDate->format('Y-m-d')) {
                // Overlap check: (start1 < end2) && (end1 > start2)
                if ($startTime->format('H:i:s') < $row->getEndTime()->format('H:i:s') &&
                    $endTime->format('H:i:s') > $row->getStartTime()->format('H:i:s')) {
                    $this->addFlash('error', 'This exception overlaps with an existing one on the same day.');
                    return $this->redirectToRoute('app_availability_manage');
                }
            }
        }

        $exception = new Availability();
        $exception->setTherapist($therapist);
        $exception->setDay(strtoupper($specificDate->format('l')));
        $exception->setStartTime($startTime);
        $exception->setEndTime($endTime);
        $exception->setIsAvailable(false);
        $exception->setSpecificDate($specificDate);
        $this->availabilityRepository->save($exception);

        $this->addFlash('success', 'Exception added successfully.');
        return $this->redirectToRoute('app_availability_manage');
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $therapist = $this->resolveTherapistForCurrentUser($request);
        if ($therapist === null) {
            throw $this->createNotFoundException('Therapist profile was not found for this user.');
        }

        $availability = $this->availabilityRepository->find($id);
        if ($availability && $availability->getTherapist()->getId() === $therapist->getId()) {
            $this->availabilityRepository->remove($availability);
            $this->addFlash('success', 'Availability entry removed.');
        }

        return $this->redirectToRoute('app_availability_manage');
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');
        $therapist = $this->resolveTherapistForCurrentUser($request);
        if ($therapist === null) {
            throw $this->createNotFoundException('Therapist profile was not found for this user.');
        }

        $availability = $this->availabilityRepository->find($id);
        if (!$availability || $availability->getTherapist()->getId() !== $therapist->getId()) {
            $this->addFlash('error', 'Availability entry not found.');
            return $this->redirectToRoute('app_availability_manage');
        }

        $start = (string) $request->request->get('start_time', '');
        $end = (string) $request->request->get('end_time', '');
        $startTime = \DateTime::createFromFormat('H:i', $start) ?: \DateTime::createFromFormat('H:i:s', $start);
        $endTime = \DateTime::createFromFormat('H:i', $end) ?: \DateTime::createFromFormat('H:i:s', $end);
        if (!$startTime || !$endTime || $endTime <= $startTime) {
            $this->addFlash('error', 'Please provide a valid time range.');
            return $this->redirectToRoute('app_availability_manage');
        }

        if ($availability->getSpecificDate() === null) {
            $day = strtoupper((string) $request->request->get('day', ''));
            if (!in_array($day, ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'], true)) {
                $this->addFlash('error', 'Please select a valid weekday.');
                return $this->redirectToRoute('app_availability_manage');
            }
            $availability->setDay($day);
        } else {
            $date = (string) $request->request->get('specific_date', '');
            $specificDate = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$specificDate) {
                $this->addFlash('error', 'Please provide a valid specific date.');
                return $this->redirectToRoute('app_availability_manage');
            }

            if ($specificDate < new \DateTime('today')) {
                $this->addFlash('error', 'You cannot update exceptions to dates in the past.');
                return $this->redirectToRoute('app_availability_manage');
            }

            $availability->setSpecificDate($specificDate);
            $availability->setDay(strtoupper($specificDate->format('l')));
        }

        // Overlap check against others
        // If updating an exception, check if it's within business hours
        if ($availability->getSpecificDate() !== null && !$availability->isAvailable()) {
            $isWithinBusinessHours = false;
            foreach ($existing as $row) {
                if ($row->getSpecificDate() === null && $row->isAvailable() && $row->getDay() === $availability->getDay()) {
                    if ($row->getStartTime()->format('H:i:s') <= $startTime->format('H:i:s')
                        && $row->getEndTime()->format('H:i:s') >= $endTime->format('H:i:s')) {
                        $isWithinBusinessHours = true;
                        break;
                    }
                }
            }
            if (!$isWithinBusinessHours) {
                $this->addFlash('error', 'Exceptions must be within your recurring business hours.');
                return $this->redirectToRoute('app_availability_manage');
            }
        }

        foreach ($existing as $row) {
            if ($row->getId() === $availability->getId()) continue;
            
            // If both are recurring on the same day or both are exceptions on the same specific date
            $sameDay = ($availability->getSpecificDate() === null && $row->getSpecificDate() === null && $availability->getDay() === $row->getDay());
            $sameDate = ($availability->getSpecificDate() !== null && $row->getSpecificDate() !== null && $availability->getSpecificDate()->format('Y-m-d') === $row->getSpecificDate()->format('Y-m-d'));
            
            if ($sameDay || $sameDate) {
                if ($startTime->format('H:i:s') < $row->getEndTime()->format('H:i:s') &&
                    $endTime->format('H:i:s') > $row->getStartTime()->format('H:i:s')) {
                    $this->addFlash('error', 'Update failed: it overlaps with another existing entry.');
                    return $this->redirectToRoute('app_availability_manage');
                }
            }
        }

        $availability->setStartTime($startTime);
        $availability->setEndTime($endTime);
        $this->availabilityRepository->save($availability);

        $this->addFlash('success', 'Availability updated successfully.');
        return $this->redirectToRoute('app_availability_manage');
    }

    private function resolveTherapistForCurrentUser(Request $request): ?\App\Entity\Therapist
    {
        if ($this->isGranted('ROLE_ADMIN') && $request->query->has('therapist_id')) {
            $selected = $this->therapistRepository->find((int) $request->query->get('therapist_id'));
            if ($selected !== null) {
                return $selected;
            }
        }

        $user = $this->getUser();
        if ($user && method_exists($user, 'getEmail') && $user->getEmail()) {
            return $this->therapistRepository->findOneByEmail((string) $user->getEmail());
        }

        return null;
    }
}
