<?php

namespace App\Support;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleSelection = 'multiple_selection';
    case TrueFalse = 'true_false';
    case Matching = 'matching';
    case FillInTheBlanks = 'fill_in_the_blanks';
    case ShortAnswer = 'short_answer';
    case Ordering = 'ordering';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Multiple choice',
            self::MultipleSelection => 'Multiple selection',
            self::TrueFalse => 'True / False',
            self::Matching => 'Matching',
            self::FillInTheBlanks => 'Fill in the blanks',
            self::ShortAnswer => 'Short answer',
            self::Ordering => 'Ordering / sequencing',
        };
    }
}
