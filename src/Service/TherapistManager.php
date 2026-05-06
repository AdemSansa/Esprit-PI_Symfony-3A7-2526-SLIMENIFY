<?php

namespace App\Service;

use App\Entity\Therapist;
use InvalidArgumentException;

class TherapistManager
{
    public function validate(Therapist $therapist): bool
    {
        if (empty($therapist->getFirstName())) {
            throw new InvalidArgumentException('Le prénom (firstName) est obligatoire');
        }

        if (empty($therapist->getLastName())) {
            throw new InvalidArgumentException('Le nom (lastName) est obligatoire');
        }

        if (!filter_var($therapist->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'email est invalide');
        }

        return true;
    }
}
