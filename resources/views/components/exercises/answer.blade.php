@props(['question', 'correct' => false])

@php
    $answer = $question[$correct ? 'correct_answer' : 'submitted_answer'];
    $content = $question['content'];
    $labels = fn (string $group) => array_column($content[$group], 'label', 'id');
    $formatItems = fn (string $group, array $ids, string $separator = ', ') =>
        implode($separator, array_map(fn ($id) => $labels($group)[$id], $ids));
    $text = match (App\Support\QuestionType::from($question['type'])) {
        App\Support\QuestionType::SingleChoice => $labels('options')[$correct ? $answer['option_id'] : $answer],
        App\Support\QuestionType::MultipleSelection => $formatItems('options', $correct ? $answer['option_ids'] : $answer),
        App\Support\QuestionType::TrueFalse => ($correct ? $answer['value'] : $answer) ? 'True' : 'False',
        App\Support\QuestionType::Matching => collect($correct ? array_column($answer['pairs'], 'right_id', 'left_id') : $answer)
            ->map(fn ($right, $left) => $labels('left')[$left].' → '.$labels('right')[$right])->implode(' · '),
        App\Support\QuestionType::FillInTheBlanks => collect($correct ? array_column($answer['slots'], 'accepted', 'id') : $answer)
            ->map(fn ($value, $id) => $labels('slots')[$id].': '.(is_array($value) ? implode(' / ', $value) : $value))->implode(' · '),
        App\Support\QuestionType::ShortAnswer => $correct ? implode(' / ', $answer['accepted']) : $answer,
        App\Support\QuestionType::Ordering => $formatItems('items', $correct ? $answer['item_ids'] : $answer, ' → '),
    };
@endphp
<bdi>{{ $text }}</bdi>
