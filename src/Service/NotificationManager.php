<?php

namespace App\Service;

use App\Entity\Notification;

class NotificationManager
{
    public function validate(Notification $notification): bool
    {
        if (empty($notification->getTitle())) {
            throw new \InvalidArgumentException('Le titre est obligatoire');
        }

        if (empty($notification->getMessage())) {
            throw new \InvalidArgumentException('Le message est obligatoire');
        }

        if ($notification->getUser() === null) {
            throw new \InvalidArgumentException('L\'utilisateur est obligatoire');
        }

        return true;
    }
}
