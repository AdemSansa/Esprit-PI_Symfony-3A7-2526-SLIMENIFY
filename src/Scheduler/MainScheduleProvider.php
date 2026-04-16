<?php

namespace App\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
class MainScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                // Run the appointment reminder command every day at 8:00 AM
                \Symfony\Component\Scheduler\RecurringMessage::cron('0 6 01 * *', new RunCommandMessage('app:appointment-reminders'))
            )
        ;
    }
}
