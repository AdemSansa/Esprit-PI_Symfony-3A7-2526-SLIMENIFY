<?php

namespace App\Service;

use App\Entity\Message;
use InvalidArgumentException;

class MessageManager
{
    public function validate(Message $message): bool
    {
        if ($message->getConversation() === null) {
            throw new InvalidArgumentException('La conversation est obligatoire');
        }

        if (empty($message->getContent())) {
            throw new InvalidArgumentException('Le contenu du message ne peut pas être vide');
        }

        $allowedSenders = ['user', 'therapist'];
        if (!in_array($message->getSenderType(), $allowedSenders)) {
            throw new InvalidArgumentException('Le type d\'expéditeur est invalide');
        }

        return true;
    }
}
