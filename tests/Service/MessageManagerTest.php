<?php

namespace App\Tests\Service;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Service\MessageManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MessageManagerTest extends TestCase
{
    public function testValidMessage()
    {
        $message = new Message();
        $message->setConversation(new Conversation());
        $message->setContent('Bonjour, comment allez-vous ?');
        $message->setSenderType('user');

        $manager = new MessageManager();

        $this->assertTrue($manager->validate($message));
    }

    public function testMessageWithoutConversation()
    {
        $this->expectException(InvalidArgumentException::class);

        $message = new Message();
        $message->setContent('Test');
        $message->setSenderType('user');

        $manager = new MessageManager();
        $manager->validate($message);
    }

    public function testMessageWithEmptyContent()
    {
        $this->expectException(InvalidArgumentException::class);

        $message = new Message();
        $message->setConversation(new Conversation());
        $message->setContent('');
        $message->setSenderType('user');

        $manager = new MessageManager();
        $manager->validate($message);
    }

    public function testMessageWithInvalidSenderType()
    {
        $this->expectException(InvalidArgumentException::class);

        $message = new Message();
        $message->setConversation(new Conversation());
        $message->setContent('Test');
        $message->setSenderType('admin'); // invalid

        $manager = new MessageManager();
        $manager->validate($message);
    }
}
