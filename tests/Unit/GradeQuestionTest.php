<?php

namespace Tests\Unit;

use App\Actions\Questions\GradeQuestion;
use App\Support\QuestionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GradeQuestionTest extends TestCase
{
    public static function definitions(): iterable
    {
        yield 'single choice' => [QuestionType::SingleChoice, ['option_id' => 'a'], 'a', 'b'];
        yield 'multiple selection' => [
            QuestionType::MultipleSelection, ['option_ids' => ['a', 'b']], ['b', 'a'], ['a', 'a'],
        ];
        yield 'true or false' => [QuestionType::TrueFalse, ['value' => false], false, 'false'];
        yield 'matching' => [
            QuestionType::Matching,
            ['pairs' => [['left_id' => 'a', 'right_id' => 'c'], ['left_id' => 'b', 'right_id' => 'd']]],
            ['b' => 'd', 'a' => 'c'],
            ['a' => 'c', 'b' => 'c'],
        ];
        yield 'fill in the blanks' => [
            QuestionType::FillInTheBlanks,
            ['slots' => [['id' => 'verb', 'accepted' => ['is', 'was']], ['id' => 'place', 'accepted' => ['in class']]]],
            ['place' => " IN\u{00A0}CLASS ", 'verb' => 'WAS'],
            ['verb' => 'was', 'other' => 'in class'],
        ];
        yield 'short answer' => [
            QuestionType::ShortAnswer, ['accepted' => ['The school was built.', 'Café.']],
            "  CAFÉ.\n", 'Cafe.',
        ];
        yield 'ordering' => [QuestionType::Ordering, ['item_ids' => ['b', 'a']], ['b', 'a'], ['a', 'b']];
    }

    #[DataProvider('definitions')]
    public function test_grades_for_full_points_or_zero_and_includes_the_explanation(
        QuestionType $type,
        array $definition,
        mixed $correct,
        mixed $incorrect,
    ): void {
        $grader = new GradeQuestion;
        $explanation = 'Review the example.';

        self::assertSame(
            ['correct' => true, 'awarded_points' => 3, 'explanation' => $explanation],
            $grader->handle($type, $definition, $correct, 3, $explanation),
        );
        self::assertSame(
            ['correct' => false, 'awarded_points' => 0, 'explanation' => $explanation],
            $grader->handle($type, $definition, $incorrect, 3, $explanation),
        );
        self::assertFalse($grader->handle($type, $definition, null, 3)['correct']);
    }
}
