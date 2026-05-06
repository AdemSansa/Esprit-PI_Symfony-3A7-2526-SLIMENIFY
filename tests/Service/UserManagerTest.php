<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser()
    {
        $user = new User();
        $user->setFirstName('Victor');
        $user->setEmail('victor.hugo@gmail.com');

        $manager = new UserManager();

        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithoutFirstName()
    {
        $this->expectException(InvalidArgumentException::class);

        $user = new User();
        $user->setFirstName(''); // empty firstName
        $user->setEmail('test@gmail.com');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithInvalidEmail()
    {
        $this->expectException(InvalidArgumentException::class);

        $user = new User();
        $user->setFirstName('Test');
        $user->setEmail('email_invalide');

        $manager = new UserManager();
        $manager->validate($user);
    }
}
