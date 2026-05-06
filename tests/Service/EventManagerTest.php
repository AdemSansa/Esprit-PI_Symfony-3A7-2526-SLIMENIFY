<?php

namespace App\Tests\Service;

use App\Entity\Event;
use App\Service\EventManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    public function testValidEvent()
    {
        $event = new Event();
        $event->setTitle('Formation Symfony');
        $event->setMaxParticipants(10);

        $manager = new EventManager();

        $this->assertTrue($manager->validate($event));
    }

    public function testEventWithoutTitle()
    {
        $this->expectException(InvalidArgumentException::class);

        $event = new Event();
        // Pas de titre
        $event->setMaxParticipants(10);

        $manager = new EventManager();
        $manager->validate($event);
    }

    public function testEventWithInvalidParticipants()
    {
        $this->expectException(InvalidArgumentException::class);

        $event = new Event();
        $event->setTitle('Formation React');
        $event->setMaxParticipants(0); // Invalide : 0

        $manager = new EventManager();
        $manager->validate($event);
    }
}
