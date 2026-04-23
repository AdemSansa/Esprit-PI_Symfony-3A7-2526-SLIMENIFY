<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[AsCommand(
    name: 'app:appointment-reminders',
    description: 'Sends daily reminders for appointments scheduled for tomorrow.',
)]
class SendAppointmentRemindersCommand extends Command
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private RouterInterface $router
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tomorrow = new \DateTime('tomorrow');
        
        $io->title('Sending Appointment Reminders for ' . $tomorrow->format('Y-m-d'));

        // Fetch pending or confirmed appointments for tomorrow
        $appointments = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.appointmentDate = :date')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('date', $tomorrow->format('Y-m-d'))
            ->setParameter('statuses', ['confirmed'])
            ->getQuery()
            ->getResult();

        if (empty($appointments)) {
            $io->success('No appointments found for tomorrow. Nothing to send.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($appointments as $appointment) {
            $patient = $appointment->getPatient();
            if (!$patient->getEmail()) {
                continue;
            }

            // Ensure absolute URLs are generated for emails
            $context = $this->router->getContext();
            // Fallback for CLI, but relying on DEFAULT_URI from .env
            
            $detailUrl = $this->router->generate('app_appointments_detail', ['id' => $appointment->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $htmlBody = $this->twig->render('emails/appointment_reminder.html.twig', [
                'appointment' => $appointment,
                'patient' => $patient,
                'detailUrl' => $detailUrl
            ]);

            $email = (new Email())
                ->from('Slimenify.team@gmail.com') // Or your configured sender
                ->to($patient->getEmail())
                ->subject('Reminder: Upcoming Appointment Tomorrow')
                ->html($htmlBody);

            try {
                $this->mailer->send($email);
                $sentCount++;
            } catch (\Exception $e) {
                $io->warning(sprintf('Failed to send email to %s: %s', $patient->getEmail(), $e->getMessage()));
            }
        }

        $io->success(sprintf('Successfully sent %d reminder(s).', $sentCount));

        return Command::SUCCESS;
    }
}
