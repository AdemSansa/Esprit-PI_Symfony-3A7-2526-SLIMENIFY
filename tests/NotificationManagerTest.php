<?php

namespace App\Tests\Service;

use App\Entity\Blog;
use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationManager;
use PHPUnit\Framework\TestCase;

class NotificationManagerTest extends TestCase
{
    private NotificationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new NotificationManager();
    }

    private function makeValidNotification(): Notification
    {
        $notification = new Notification();
        $notification->setTitle('Nouveau like');
        $notification->setMessage('Votre article a été aimé.');
        $notification->setUser(new User());
        return $notification;
    }

    public function testValidateReturnsTrueForValidNotification(): void
    {
        $result = $this->manager->validate($this->makeValidNotification());

        $this->assertTrue($result);
    }

    public function testValidateReturnsTrueWithBlogSet(): void
    {
        $notification = $this->makeValidNotification();
        $notification->setBlog(new Blog());

        $this->assertTrue($this->manager->validate($notification));
    }

    public function testValidateThrowsExceptionWhenTitleIsEmpty(): void
    {
        $notification = $this->makeValidNotification();
        $notification->setTitle('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $this->manager->validate($notification);
    }

    public function testValidateThrowsExceptionWhenTitleIsNull(): void
    {
        $notification = new Notification();
        $notification->setMessage('Un message valide');
        $notification->setUser(new User());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $this->manager->validate($notification);
    }

    public function testValidateThrowsExceptionWhenMessageIsEmpty(): void
    {
        $notification = $this->makeValidNotification();
        $notification->setMessage('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le message est obligatoire');

        $this->manager->validate($notification);
    }

    public function testValidateThrowsExceptionWhenMessageIsNull(): void
    {
        $notification = new Notification();
        $notification->setTitle('Un titre valide');
        $notification->setUser(new User());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le message est obligatoire');

        $this->manager->validate($notification);
    }

    public function testValidateThrowsExceptionWhenUserIsNull(): void
    {
        $notification = new Notification();
        $notification->setTitle('Un titre valide');
        $notification->setMessage('Un message valide');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur est obligatoire');

        $this->manager->validate($notification);
    }

    public function testValidateChecksTitleBeforeMessage(): void
    {
        $notification = new Notification();
        // titre et message invalides — titre vérifié en premier
        $notification->setUser(new User());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $this->manager->validate($notification);
    }

    public function testValidateChecksMessageBeforeUser(): void
    {
        $notification = new Notification();
        $notification->setTitle('Titre valide');
        // message et user manquants — message vérifié en premier

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le message est obligatoire');

        $this->manager->validate($notification);
    }
}
