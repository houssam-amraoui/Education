@if($report === 'engagement')
    <ul>
        <li>Lessons completed · {{ $metrics['lesson_completions'] }}</li>
        <li>Exercises completed · {{ $metrics['exercise_completions'] }}</li>
        <li>Repeat exercise attempts · {{ $metrics['exercise_repeat_attempts'] }}</li>
        <li>Exams attempted · {{ $metrics['exam_attempts'] }}</li>
        <li>Exams completed · {{ $metrics['exam_completions'] }}</li>
        <li>Video playbacks · {{ $metrics['video_playbacks'] }}</li>
        <li>Students with consecutive learning days · {{ $metrics['students_with_consecutive_learning_days'] }}</li>
        @foreach($metrics['monthly_active_users'] as $month => $total)
            <li>Active students in <bdi>{{ $month }}</bdi> · {{ $total }}</li>
        @endforeach
    </ul>
@elseif(in_array($report, ['learning', 'overview'], true))
    @php($learning = $report === 'learning' ? $metrics : $metrics['learning'])
    <p>Exercise completion rate · {{ $percent($learning['exercises']['completion_rate']) ?? 'No data' }}</p>
    @foreach(['exercise' => 'Exercise', 'exam' => 'Exam'] as $type => $label)
        @php($questions = array_slice($learning[$type.'_question_difficulty'], 0, 3))
        @if($questions)
            <ul>
                @foreach($questions as $question)
                    <li>{{ $label }} question #{{ $question['question_id'] }} · {{ $question['incorrect'] }} of {{ $question['graded'] }} incorrect</li>
                @endforeach
            </ul>
        @endif
    @endforeach
    @if(! $learning['exercise_question_difficulty'] && ! $learning['exam_question_difficulty'])
        <p>No graded questions in this period.</p>
    @endif
@elseif($report === 'communication')
    <p>Responding teachers · {{ $metrics['responding_teachers'] }}</p>
    <p>Average first reply time · {{ $metrics['average_first_reply_seconds'] === null ? 'No first replies in this period' : $metrics['average_first_reply_seconds'].' seconds' }}</p>
@else
    <p>Review the learning report for completion rates and common difficulties.</p>
    <x-prototype.link :href="$url('A47')">Open learning report</x-prototype.link>
@endif
