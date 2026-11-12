<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionDefinition
{
    public static function rules(mixed $type): array
    {
        $type = is_string($type) ? QuestionType::tryFrom($type) : null;
        $rules = [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'max:10000'],
            'points' => ['required', 'integer', 'min:0', 'max:65535'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'content' => ['present', 'array'],
            'answer' => ['required', 'array'],
        ];

        return array_replace($rules, match ($type) {
            QuestionType::SingleChoice => self::items('options', 2) + [
                'answer' => ['required', 'array:option_id'],
                'answer.option_id' => self::id(),
            ],
            QuestionType::MultipleSelection => self::items('options', 2) + self::ids('option_ids'),
            QuestionType::TrueFalse => [
                'content' => ['present', 'array', 'size:0'],
                'answer' => ['required', 'array:value'],
                'answer.value' => ['required', 'boolean'],
            ],
            QuestionType::Matching => self::items('left,right', 2) + [
                'answer' => ['required', 'array:pairs'],
                'answer.pairs' => ['required', 'array', 'list', 'max:50'],
                'answer.pairs.*' => ['required', 'array:left_id,right_id'],
                'answer.pairs.*.left_id' => [...self::id(), 'distinct:strict'],
                'answer.pairs.*.right_id' => [...self::id(), 'distinct:strict'],
            ],
            QuestionType::FillInTheBlanks => self::items('slots', 1) + [
                'answer' => ['required', 'array:slots'],
                'answer.slots' => ['required', 'array', 'list', 'max:50'],
                'answer.slots.*' => ['required', 'array:id,accepted'],
                'answer.slots.*.id' => [...self::id(), 'distinct:strict'],
                ...self::accepted('answer.slots.*.accepted'),
            ],
            QuestionType::ShortAnswer => [
                'content' => ['present', 'array', 'size:0'],
                'answer' => ['required', 'array:accepted'],
                ...self::accepted('answer.accepted'),
            ],
            QuestionType::Ordering => self::items('items', 2) + self::ids('item_ids'),
            null => [],
        });
    }

    public static function validateReferences(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->validated();
        $content = $data['content'];
        $answer = $data['answer'];

        switch (QuestionType::from($data['type'])) {
            case QuestionType::SingleChoice:
                if (! in_array($answer['option_id'], array_column($content['options'], 'id'), true)) {
                    $validator->errors()->add('answer.option_id', 'Select an option defined for this question.');
                }
                break;
            case QuestionType::MultipleSelection:
                if (array_diff($answer['option_ids'], array_column($content['options'], 'id')) !== []) {
                    $validator->errors()->add('answer.option_ids', 'Use only options defined for this question.');
                }
                break;
            case QuestionType::TrueFalse:
            case QuestionType::ShortAnswer:
                break;
            case QuestionType::Matching:
                if (! self::sameIds(array_column($answer['pairs'], 'left_id'), array_column($content['left'], 'id'))
                    || ! self::sameIds(array_column($answer['pairs'], 'right_id'), array_column($content['right'], 'id'))) {
                    $validator->errors()->add('answer.pairs', 'Pair each left item with exactly one right item and use every item.');
                }
                break;
            case QuestionType::FillInTheBlanks:
                if (! self::sameIds(array_column($answer['slots'], 'id'), array_column($content['slots'], 'id'))) {
                    $validator->errors()->add('answer.slots', 'Define accepted answers for every blank.');
                }
                if (substr_count($data['prompt'], '___') !== count($content['slots'])) {
                    $validator->errors()->add('prompt', 'Include one ___ marker per blank.');
                }
                break;
            case QuestionType::Ordering:
                if (! self::sameIds($answer['item_ids'], array_column($content['items'], 'id'))) {
                    $validator->errors()->add('answer.item_ids', 'Include each item exactly once in the correct order.');
                }
                break;
        }
    }

    public static function sameIds(array $actual, array $expected): bool
    {
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);

        return $actual === $expected;
    }

    public static function normalizeText(string $text): ?string
    {
        $collapsed = preg_replace('/[\s\p{Z}]+/u', ' ', $text);

        return $collapsed === null ? null : mb_convert_case(trim($collapsed), MB_CASE_FOLD, 'UTF-8');
    }

    private static function items(string $groups, int $minimum): array
    {
        $rules = ['content' => ['required', "array:{$groups}"]];
        foreach (explode(',', $groups) as $group) {
            $key = "content.{$group}";
            $rules[$key] = ['required', 'array', 'list', "min:{$minimum}", 'max:50'];
            $rules["{$key}.*"] = ['required', 'array:id,label'];
            $rules["{$key}.*.id"] = [...self::id(), 'distinct:strict'];
            $rules["{$key}.*.label"] = ['required', 'string', 'max:2000'];
        }

        return $rules;
    }

    private static function ids(string $key): array
    {
        return [
            'answer' => ['required', "array:{$key}"],
            "answer.{$key}" => ['required', 'array', 'list', 'max:50'],
            "answer.{$key}.*" => [...self::id(), 'distinct:strict'],
        ];
    }

    private static function id(): array
    {
        return ['required', 'string', 'regex:/\A[a-zA-Z][a-zA-Z0-9_-]{0,63}\z/'];
    }

    private static function accepted(string $key): array
    {
        return [
            $key => ['required', 'array', 'list', 'max:50'],
            "{$key}.*" => ['bail', 'required', 'string', 'max:2000', function ($attribute, string $value, $fail): void {
                if (in_array(self::normalizeText($value), ['', null], true)) {
                    $fail('Enter a non-empty accepted answer.');
                }
            }],
        ];
    }
}
