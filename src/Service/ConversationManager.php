<?php

namespace App\Service;

use App\Entity\Conversation;
use InvalidArgumentException;

class ConversationManager
{
    public function validate(Conversation $conversation): bool
    {
        if ($conversation->getUser() === null) {
            throw new InvalidArgumentException('Le patient (user) est obligatoire');
        }

        if ($conversation->getTherapist() === null) {
            throw new InvalidArgumentException('Le thérapeute est obligatoire');
        }

        return true;
    }
}
