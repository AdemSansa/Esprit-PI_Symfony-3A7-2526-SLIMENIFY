<?php

namespace App\Service;

use mofodojodino\ProfanityFilter\Check;


class ModerationService
{
    public function __construct(
        private Check $profanityCheck,
        private bool $profanityCheckEnabled = true,
    ) {
    }

    public function checkText(string $text): bool
    {
        if (!$this->profanityCheckEnabled) {
            return false;
        }

        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return $this->profanityCheck->hasProfanity($text);
    }
}
