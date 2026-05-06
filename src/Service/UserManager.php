<?php

namespace App\Service;

use App\Entity\User;
use InvalidArgumentException;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getFirstName())) {
            throw new InvalidArgumentException('Le prénom (firstName) est obligatoire');
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'email est invalide');
        }

        return true;
    }
}
