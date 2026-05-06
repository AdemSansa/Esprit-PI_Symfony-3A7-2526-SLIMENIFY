<?php

namespace App\Service;

use App\Entity\Event;
use InvalidArgumentException;

class EventManager
{
    public function validate(Event $event): bool
    {
        if (empty($event->getTitle())) {
            throw new InvalidArgumentException('Le titre de l\'événement est obligatoire');
        }

        if ($event->getMaxParticipants() === null || $event->getMaxParticipants() <= 0) {
            throw new InvalidArgumentException('Le nombre de participants doit être supérieur à 0');
        }

        return true;
    }
}
