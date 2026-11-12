<?php

namespace App\Actions\Questions;

use App\Support\QuestionDefinition;
use App\Support\QuestionType;

class GradeQuestion
{
    /**
     * Grade against a validated answer definition.
     *
     * @return array{correct: bool, awarded_points: int, explanation: ?string}
     */
    public function handle(
        QuestionType $type,
        array $answer,
        mixed $submission,
        int $points,
        ?string $explanation = null,
    ): array {
        $correct = match ($type) {
            QuestionType::SingleChoice => $submission === $answer['option_id'],
            QuestionType::MultipleSelection => is_array($submission) && array_is_list($submission)
                && array_filter($submission, is_string(...)) === $submission
                && QuestionDefinition::sameIds($submission, $answer['option_ids']),
            QuestionType::TrueFalse => $submission === (bool) $answer['value'],
            QuestionType::Matching => $this->matchesPairs($submission, $answer['pairs']),
            QuestionType::FillInTheBlanks => $this->matchesSlots($submission, $answer['slots']),
            QuestionType::ShortAnswer => $this->acceptsText($submission, $answer['accepted']),
            QuestionType::Ordering => $submission === $answer['item_ids'],
        };

        return [
            'correct' => $correct,
            'awarded_points' => $correct ? $points : 0,
            'explanation' => $explanation,
        ];
    }

    private function matchesPairs(mixed $submission, array $pairs): bool
    {
        if (! is_array($submission)) {
            return false;
        }

        $expected = array_column($pairs, 'right_id', 'left_id');

        // Strictly compare the mapping without depending on key order.
        ksort($submission);
        ksort($expected);

        return $submission === $expected;
    }

    private function matchesSlots(mixed $submission, array $slots): bool
    {
        if (! is_array($submission) || count($submission) !== count($slots)) {
            return false;
        }

        foreach ($slots as $slot) {
            if (! $this->acceptsText($submission[$slot['id']] ?? null, $slot['accepted'])) {
                return false;
            }
        }

        return true;
    }

    private function acceptsText(mixed $submission, array $accepted): bool
    {
        $text = is_string($submission) ? QuestionDefinition::normalizeText($submission) : null;

        return $text !== null && $text !== ''
            && in_array($text, array_map(QuestionDefinition::normalizeText(...), $accepted), true);
    }
}
