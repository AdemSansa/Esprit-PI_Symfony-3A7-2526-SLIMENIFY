<?php

namespace App\Tests;

use App\Entity\Availability;
use App\Entity\Therapist;
use PHPUnit\Framework\TestCase;

class AvailabilityTest extends TestCase
{
    public function testDefaultIsAvailable(): void
    {
        $availability = new Availability();
        $this->assertTrue($availability->isAvailable());
    }

    public function testSetAndGetDay(): void
    {
        $availability = new Availability();
        $availability->setDay('MONDAY');

        $this->assertSame('MONDAY', $availability->getDay());
    }

    public function testSetAndGetStartAndEndTime(): void
    {
        $availability = new Availability();
        $start = new \DateTime('09:00:00');
        $end = new \DateTime('17:00:00');
        $availability->setStartTime($start);
        $availability->setEndTime($end);

        $this->assertSame($start, $availability->getStartTime());
        $this->assertSame($end, $availability->getEndTime());
    }

    public function testSetIsAvailable(): void
    {
        $availability = new Availability();
        $availability->setIsAvailable(false);

        $this->assertFalse($availability->isAvailable());
    }

    public function testSetAndGetSpecificDate(): void
    {
        $availability = new Availability();
        $date = new \DateTime('2026-12-25');
        $availability->setSpecificDate($date);

        $this->assertSame($date, $availability->getSpecificDate());
    }

    public function testClearSpecificDate(): void
    {
        $availability = new Availability();
        $availability->setSpecificDate(new \DateTime('2026-06-01'));
        $availability->setSpecificDate(null);

        $this->assertNull($availability->getSpecificDate());
    }

    public function testSetAndGetTherapist(): void
    {
        $availability = new Availability();
        $therapist = $this->createMock(Therapist::class);
        $availability->setTherapist($therapist);

        $this->assertSame($therapist, $availability->getTherapist());
    }
}
