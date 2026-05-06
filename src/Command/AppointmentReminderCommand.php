<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:appointment:send-reminders',
    description: 'Sends email reminders to patients for appointments tomorrow',
)]
class AppointmentReminderCommand extends Command
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Does not actually send emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $tomorrow = new \DateTime('tomorrow');
        $appointments = $this->appointmentRepository->findByDate($tomorrow);

        if (empty($appointments)) {
            $io->info('No appointments found for ' . $tomorrow->format('Y-m-d'));
            return Command::SUCCESS;
        }

        $io->progressStart(count($appointments));
        $sentCount = 0;

        foreach ($appointments as $appointment) {
            $patient = $appointment->getPatient();
            $therapist = $appointment->getTherapist();

            $emailBody = sprintf(
                "Hello %s,\n\nThis is a reminder for your appointment with %s tomorrow, %s at %s.\n\nType: %s\nLocation: %s\n\nSee you then!",
                $patient->getFirstName(),
                $therapist->getFirstName() . ' ' . $therapist->getLastName(),
                $appointment->getAppointmentDate()->format('Y-m-d'),
                $appointment->getStartTime()->format('H:i'),
                ucfirst((string) $appointment->getType()),
                strtolower((string) $appointment->getType()) === 'video' ? 'Online (check portal for link)' : 'In-person'
            );

            $patientEmail = $patient->getEmail();
            if (!$patientEmail) {
                continue;
            }

            $email = (new Email())
                ->from('slimenify.team@gmail.com')
                ->to($patientEmail)
                ->subject('Appointment Reminder: Tomorrow at ' . $appointment->getStartTime()->format('H:i'))
                ->text($emailBody);

            if (!$dryRun) {
                try {
                    $this->mailer->send($email);
                    $sentCount++;
                } catch (\Exception $e) {
                    $io->error('Failed to send email to ' . $patient->getEmail() . ': ' . $e->getMessage());
                }
            } else {
                $sentCount++;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(sprintf('Sent %d reminders for %s %s', $sentCount, $tomorrow->format('Y-m-d'), $dryRun ? '(Dry Run)' : ''));

        return Command::SUCCESS;
    }
}
