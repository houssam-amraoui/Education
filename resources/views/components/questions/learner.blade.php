@props(['questions'])

@forelse ($questions as $question)
    @php
        $id = $question['id'];
        $name = 'answers['.$id.']';
        $number = $loop->iteration;
        $content = $question['content'];
        $type = App\Support\QuestionType::from($question['type']);
        $multiple = $type === App\Support\QuestionType::MultipleSelection;
        $submitted = old('answers.'.$id);
        $questionErrors = [...$errors->get('answers.'.$id), ...array_merge(...array_values($errors->get('answers.'.$id.'.*')))];
        $errorId = 'answer-error-'.$id;
    @endphp
    <x-prototype.card>
        <fieldset data-learner-question="{{ $id }}" data-answer-type="{{ $type->value }}" @if($questionErrors) aria-describedby="{{ $errorId }}" @endif>
            <legend dir="auto">{{ $number }}. {{ $question['prompt'] }}</legend>
            @switch($type)
                @case(App\Support\QuestionType::SingleChoice)
                @case(App\Support\QuestionType::MultipleSelection)
                    @foreach ($content['options'] as $option)
                        <label class="choice" dir="auto">
                            <input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" value="{{ $option['id'] }}"
                                @checked($multiple ? in_array($option['id'], (array) $submitted, true) : $submitted === $option['id'])
                                @required(!$multiple && $loop->first)> {{ $option['label'] }}
                        </label>
                    @endforeach
                    @break
                @case(App\Support\QuestionType::TrueFalse)
                    <label class="choice"><input type="radio" name="{{ $name }}" value="1" required @checked(in_array($submitted, [true, '1'], true))> True</label>
                    <label class="choice"><input type="radio" name="{{ $name }}" value="0" @checked(in_array($submitted, [false, '0'], true))> False</label>
                    @break
                @case(App\Support\QuestionType::Matching)
                    @foreach ($content['left'] as $item)
                        @php
                            $fieldId = 'question-'.$id.'-'.$loop->iteration;
                        @endphp
                        <div class="field">
                            <label for="{{ $fieldId }}" dir="auto">{{ $item['label'] }}</label>
                            <select id="{{ $fieldId }}" name="{{ $name }}[{{ $item['id'] }}]" data-answer-item="{{ $item['id'] }}" required>
                                <option value="">Choose a match</option>
                                @foreach ($content['right'] as $choice)
                                    <option value="{{ $choice['id'] }}" @selected(($submitted[$item['id']] ?? null) === $choice['id'])>{{ $choice['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    @break
                @case(App\Support\QuestionType::FillInTheBlanks)
                    @foreach ($content['slots'] as $slot)
                        @php
                            $value = $submitted[$slot['id']] ?? '';
                        @endphp
                        <label class="field" dir="auto">{{ $slot['label'] }}
                            <input name="{{ $name }}[{{ $slot['id'] }}]" data-answer-item="{{ $slot['id'] }}" value="{{ is_string($value) ? $value : '' }}" maxlength="2000" dir="auto" required>
                        </label>
                    @endforeach
                    @break
                @case(App\Support\QuestionType::ShortAnswer)
                    <label class="field"><span class="sr-only">Answer to question {{ $number }}</span>
                        <textarea name="{{ $name }}" dir="auto" maxlength="2000" required>{{ is_string($submitted) ? $submitted : '' }}</textarea>
                    </label>
                    @break
                @case(App\Support\QuestionType::Ordering)
                    <p>Choose a position for each part. No dragging required.</p>
                    @foreach ($content['items'] as $item)
                        @php
                            $position = $submitted[$item['id']] ?? null;
                            $fieldId = 'question-'.$id.'-'.$loop->iteration;
                        @endphp
                        <div class="field">
                            <label for="{{ $fieldId }}" dir="auto">{{ $item['label'] }}</label>
                            <select id="{{ $fieldId }}" name="{{ $name }}[{{ $item['id'] }}]" data-answer-item="{{ $item['id'] }}" required>
                                <option value="">Position</option>
                                @foreach ($content['items'] as $choice)
                                    <option value="{{ $loop->iteration }}" @selected(is_scalar($position) && (string) $position === (string) $loop->iteration)>{{ $loop->iteration }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    @break
            @endswitch
            @if ($questionErrors)
                <p class="notice danger" id="{{ $errorId }}">{{ implode(' ', $questionErrors) }}</p>
            @endif
        </fieldset>
    </x-prototype.card>
@empty
    <div class="empty-state"><h2>No questions yet</h2><p>This assessment has no questions to answer.</p></div>
@endforelse
