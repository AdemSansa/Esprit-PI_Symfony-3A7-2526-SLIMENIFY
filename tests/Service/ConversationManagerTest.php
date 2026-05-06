<?php

namespace App\Tests\Service;

use App\Entity\Conversation;
use App\Entity\User;
use App\Entity\Therapist;
use App\Service\ConversationManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConversationManagerTest extends TestCase
{
    public function testValidConversation()
    {
        $conversation = new Conversation();
        $conversation->setUser(new User());
        $conversation->setTherapist(new Therapist());

        $manager = new ConversationManager();

        $this->assertTrue($manager->validate($conversation));
    }

    public function testConversationWithoutUser()
    {
        $this->expectException(InvalidArgumentException::class);

        $conversation = new Conversation();
        $conversation->setTherapist(new Therapist()); // no user

        $manager = new ConversationManager();
        $manager->validate($conversation);
    }

    public function testConversationWithoutTherapist()
    {
        $this->expectException(InvalidArgumentException::class);

        $conversation = new Conversation();
        $conversation->setUser(new User()); // no therapist

        $manager = new ConversationManager();
        $manager->validate($conversation);
    }
}
