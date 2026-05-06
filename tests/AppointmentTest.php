<?php

namespace App\Tests;

use App\Entity\Appointment;
use App\Entity\Therapist;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase
{
    public function testInitialization(): void
    {
        $appointment = new Appointment();
        $this->assertInstanceOf(\DateTime::class, $appointment->getCreatedAt());
        $this->assertInstanceOf(Collection::class, $appointment->getNotes());
        $this->assertCount(0, $appointment->getNotes());
    }

    public function testSetAndGetAppointmentDate(): void
    {
        $appointment = new Appointment();
        $date = new \DateTime('2026-04-30');
        $appointment->setAppointmentDate($date);

        $this->assertSame($date, $appointment->getAppointmentDate());
    }

    public function testSetAndGetStartTime(): void
    {
        $appointment = new Appointment();
        $time = new \DateTime('09:00:00');
        $appointment->setStartTime($time);

        $this->assertSame($time, $appointment->getStartTime());
    }

    public function testSetAndGetEndTime(): void
    {
        $appointment = new Appointment();
        $time = new \DateTime('10:00:00');
        $appointment->setEndTime($time);

        $this->assertSame($time, $appointment->getEndTime());
    }

    public function testSetAndGetStatus(): void
    {
        $appointment = new Appointment();
        $status = 'confirmed';
        $appointment->setStatus($status);

        $this->assertEquals($status, $appointment->getStatus());
    }

    public function testSetAndGetType(): void
    {
        $appointment = new Appointment();
        $type = 'video';
        $appointment->setType($type);

        $this->assertEquals($type, $appointment->getType());
    }

    public function testSetAndGetPatientMood(): void
    {
        $appointment = new Appointment();
        $mood = 'happy';
        $appointment->setPatientMood($mood);

        $this->assertEquals($mood, $appointment->getPatientMood());
    }

    public function testSetAndGetTherapist(): void
    {
        $appointment = new Appointment();
        $therapist = $this->createMock(Therapist::class);
        $appointment->setTherapist($therapist);

        $this->assertSame($therapist, $appointment->getTherapist());
    }

    public function testSetAndGetPatient(): void
    {
        $appointment = new Appointment();
        $patient = $this->createMock(User::class);
        $appointment->setPatient($patient);

        $this->assertSame($patient, $appointment->getPatient());
    }
}
