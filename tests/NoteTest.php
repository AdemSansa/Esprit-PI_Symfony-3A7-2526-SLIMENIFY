<?php

namespace App\Tests;

use App\Entity\Appointment;
use App\Entity\Note;
use App\Entity\Therapist;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    public function testInitialization(): void
    {
        $note = new Note();
        $this->assertInstanceOf(\DateTimeImmutable::class, $note->getCreatedAt());
    }

    public function testSetAndGetContent(): void
    {
        $note = new Note();
        $note->setContent('Patient showed good progress.');

        $this->assertSame('Patient showed good progress.', $note->getContent());
    }

    public function testSetAndGetMood(): void
    {
        $note = new Note();
        $note->setMood('calm');

        $this->assertSame('calm', $note->getMood());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $note = new Note();
        $created = new \DateTimeImmutable('2026-05-01 14:30:00');
        $note->setCreatedAt($created);

        $this->assertSame($created, $note->getCreatedAt());
    }

    public function testSetAndGetAppointment(): void
    {
        $note = new Note();
        $appointment = $this->createMinimalAppointment();

        $note->setAppointment($appointment);

        $this->assertSame($appointment, $note->getAppointment());
    }

    public function testSetAndGetTherapist(): void
    {
        $note = new Note();
        $therapist = $this->createMock(Therapist::class);
        $note->setTherapist($therapist);

        $this->assertSame($therapist, $note->getTherapist());
    }

    private function createMinimalAppointment(): Appointment
    {
        $therapist = $this->createMock(Therapist::class);
        $patient = $this->createMock(User::class);

        $appointment = new Appointment();
        $appointment->setTherapist($therapist);
        $appointment->setPatient($patient);
        $appointment->setAppointmentDate(new \DateTime('2026-05-07'));
        $appointment->setStartTime(new \DateTime('09:00:00'));
        $appointment->setEndTime(new \DateTime('10:00:00'));

        return $appointment;
    }
}
