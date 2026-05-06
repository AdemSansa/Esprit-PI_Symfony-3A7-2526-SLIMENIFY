<?php

namespace App\Tests\Service;

use App\Entity\Therapist;
use App\Service\TherapistManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TherapistManagerTest extends TestCase
{
    public function testValidTherapist()
    {
        $therapist = new Therapist();
        $therapist->setFirstName('Jean');
        $therapist->setLastName('Dupont');
        $therapist->setEmail('jean.dupont@gmail.com');

        $manager = new TherapistManager();

        $this->assertTrue($manager->validate($therapist));
    }

    public function testTherapistWithoutFirstName()
    {
        $this->expectException(InvalidArgumentException::class);

        $therapist = new Therapist();
        $therapist->setFirstName(''); // empty
        $therapist->setLastName('Dupont');
        $therapist->setEmail('test@gmail.com');

        $manager = new TherapistManager();
        $manager->validate($therapist);
    }

    public function testTherapistWithoutLastName()
    {
        $this->expectException(InvalidArgumentException::class);

        $therapist = new Therapist();
        $therapist->setFirstName('Jean');
        $therapist->setLastName(''); // empty
        $therapist->setEmail('test@gmail.com');

        $manager = new TherapistManager();
        $manager->validate($therapist);
    }

    public function testTherapistWithInvalidEmail()
    {
        $this->expectException(InvalidArgumentException::class);

        $therapist = new Therapist();
        $therapist->setFirstName('Jean');
        $therapist->setLastName('Dupont');
        $therapist->setEmail('email_invalide');

        $manager = new TherapistManager();
        $manager->validate($therapist);
    }
}
