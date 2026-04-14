<?php

namespace App\Enum;

enum PsychologyCategory: string
{
    case Anxiety = 'Anxiety';
    case Depression = 'Depression';
    case StressManagement = 'Stress Management';
    case Trauma = 'Trauma';
    case BehavioralTherapy = 'Behavioral Therapy';
    case SelfEsteem = 'Self-Esteem';
    case Relationships = 'Relationships';
    case SleepDisorders = 'Sleep Disorders';
    case Addiction = 'Addiction';
    case GeneralPsychology = 'General Psychology';

    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        return $choices;
    }
}
