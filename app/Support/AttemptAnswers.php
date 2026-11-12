<?php

namespace App\Support;

use App\Models\ExamAttemptItem;
use App\Models\ExerciseAttemptItem;
use Illuminate\Validation\ValidationException;

class AttemptAnswers
{
    /** @param iterable<ExamAttemptItem|ExerciseAttemptItem> $items */
    public static function validate(iterable $items, array $answers): void
    {
        $errors = [];
        $expected = [];

        foreach ($items as $item) {
            $expected[$item->id] = true;
            if (! self::validAnswer($item, $answers[$item->id] ?? null)) {
                $errors['answers.'.$item->id] = 'Enter a valid answer for this question.';
            }
        }

        if (array_diff_key($answers, $expected) !== []) {
            $errors['answers'] = 'Submit answers only for questions in this attempt.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private static function validAnswer(ExamAttemptItem|ExerciseAttemptItem $item, mixed $answer): bool
    {
        $content = $item->content;

        return match ($item->type) {
            QuestionType::SingleChoice => in_array($answer, array_column($content['options'], 'id'), true),
            QuestionType::MultipleSelection => is_array($answer) && array_is_list($answer) && $answer !== []
                && self::usesDistinctIds($answer, array_column($content['options'], 'id')),
            QuestionType::TrueFalse => is_bool($answer),
            QuestionType::Matching => self::hasKeys($answer, $content['left'])
                && self::usesDistinctIds(array_values($answer), array_column($content['right'], 'id')),
            QuestionType::FillInTheBlanks => self::hasKeys($answer, $content['slots'])
                && array_filter($answer, self::validText(...)) === $answer,
            QuestionType::ShortAnswer => self::validText($answer),
            QuestionType::Ordering => is_array($answer) && array_is_list($answer)
                && array_filter($answer, is_string(...)) === $answer
                && QuestionDefinition::sameIds($answer, array_column($content['items'], 'id')),
        };
    }

    private static function usesDistinctIds(array $values, array $allowed): bool
    {
        return count($values) <= count($allowed)
            && array_filter($values, is_string(...)) === $values
            && count(array_unique($values)) === count($values)
            && array_diff($values, $allowed) === [];
    }

    private static function hasKeys(mixed $answer, array $items): bool
    {
        return is_array($answer) && QuestionDefinition::sameIds(array_keys($answer), array_column($items, 'id'));
    }

    private static function validText(mixed $value): bool
    {
        return is_string($value) && mb_strlen($value) <= 2000
            && ! in_array(QuestionDefinition::normalizeText($value), ['', null], true);
    }
}
