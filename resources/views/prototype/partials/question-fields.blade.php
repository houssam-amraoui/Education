@php
    $types = ['Multiple choice', 'Multiple selection', 'True / False', 'Matching', 'Fill in the blanks', 'Short answer', 'Ordering / sequencing'];
    $options = [
        'Multiple choice' => ['They write the report.', 'The report is written by them.', 'The report writes them.'],
        'Multiple selection' => ['Written', 'Went', 'Seen', 'Speak'],
    ];
@endphp
<label class="field" for="question-type">Question type
    <select id="question-type" name="question-type" data-question-type>
        @foreach($types as $type)
            <option>{{ $type }}</option>
        @endforeach
    </select>
</label>
<x-prototype.field label="Question prompt" name="question-prompt" type="textarea" value="Choose the correct passive sentence." :required="true" data-required-text/>

@foreach($options as $type => $choices)
    @php($multiple = $type === 'Multiple selection')
    <fieldset data-question-fields="{{ $type }}" @if($multiple) hidden disabled @endif
        data-required-selection="{{ $multiple ? 'Select at least one correct answer.' : 'Select the correct answer.' }}">
        <legend>{{ $multiple ? 'Options and correct answers' : 'Options and correct answer' }}</legend>
        @foreach($choices as $choice)
            <x-prototype.field :label="'Option '.$loop->iteration" :name="($multiple ? 'multiple' : 'single').'-option-'.$loop->iteration" :value="$choice" :required="true" data-required-text/>
            <label class="choice">
                <input type="{{ $multiple ? 'checkbox' : 'radio' }}"
                    name="{{ $multiple ? 'multiple-correct' : 'single-correct' }}"
                    id="{{ $multiple ? 'multiple' : 'single' }}-correct-{{ $loop->iteration }}"
                    value="{{ $loop->iteration }}" data-validation-label="{{ $multiple ? 'Correct answers' : 'Correct answer' }}"
                    @checked($multiple ? in_array($loop->iteration, [1, 3]) : $loop->iteration === 2)>
                Mark option {{ $loop->iteration }} correct
            </label>
        @endforeach
    </fieldset>
@endforeach
<fieldset data-question-fields="True / False" hidden disabled data-required-selection="Select the correct answer.">
    <legend>Correct answer</legend>
    <label class="choice"><input type="radio" id="correct-true" name="true-false-correct" value="true" data-validation-label="Correct answer" checked> True</label>
    <label class="choice"><input type="radio" id="correct-false" name="true-false-correct" value="false"> False</label>
</fieldset>
<fieldset data-question-fields="Fill in the blanks" hidden disabled>
    <legend>Blank answers</legend>
    <x-prototype.field label="Accepted answers by blank" name="blank-answers" type="textarea" value="is" :required="true" data-required-text
        help="Use ___ for each blank in the prompt. Enter one line per blank in prompt order. Separate alternatives for the same blank with |."/>
</fieldset>
<fieldset data-question-fields="Short answer" hidden disabled>
    <legend>Short answer</legend>
    <x-prototype.field label="Accepted answers (one per line)" name="short-answers" type="textarea"
        :value='"The school was built.\nThe school was built by them."' :required="true" data-required-text/>
</fieldset>
<fieldset data-question-fields="Matching|Ordering / sequencing" hidden disabled>
    <x-prototype.field label="Answer options (one per line)" name="question-options" type="textarea"
        :value='"They write the report.\nThe report is written by them.\nThe report writes them."'/>
    <x-prototype.field label="Correct answer or answer mapping" name="correct-answer" value="The report is written by them."
        help="Matching and ordering use a text mapping in this visual preview."/>
</fieldset>
<x-prototype.field label="Points" name="question-points" type="number" value="2" min="0"/>
<x-prototype.field label="Correction explanation" name="explanation" type="textarea" value="Use is + past participle to form the present simple passive."/>
