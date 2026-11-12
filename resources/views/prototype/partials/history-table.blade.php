@php
    $isExam = !empty($examAttempts);
    $attempts = $isExam ? $examAttempts : ($exerciseAttempts ?? []);
    $activityTitle = $isExam ? 'Unit 1 · Practice exam' : 'Passive voice practice';
    $rows = [];
    foreach ($attempts as $number => $attempt) {
        $parameters = ['attempt' => $number] + ($isExam ? $examTiming : []);
        $rows[] = ["Attempt {$number}", $attempt['date'], $attempt['score'].' / 20', $url($isExam ? 'S08' : 'S05', $parameters)];
    }
    if (!$rows) {
        foreach ([['Passive voice practice','29 Oct 2026','16 / 20','S05'],['Unit 1 · Practice exam','27 Oct 2026','15 / 20','S08'],['Grammar review','26 Oct 2026','17 / 20','S05']] as [$name, $date, $score, $target]) {
            $rows[] = [$name, $date, $score, $url($target)];
        }
    }
@endphp
<div class="table-scroll" role="region" aria-label="{{ $attempts ? ($isExam ? 'Sample exam attempts' : 'Sample exercise attempts') : 'Sample learning activity' }}" tabindex="0"><table><caption class="sr-only">{{ $attempts ? $activityTitle.': Sample attempts' : 'Recent learning activity · Sample data' }}</caption><thead><tr><th scope="col">{{ $attempts ? 'Attempt' : 'Learning activity' }}</th><th scope="col">Date</th><th scope="col">Score</th><th scope="col">Status</th><th scope="col">Review</th></tr></thead><tbody>@foreach($rows as [$name, $date, $score, $reviewUrl])<tr><td><strong>{{ $name }}</strong><small>{{ $attempts ? $activityTitle : '2nd Baccalaureate · Unit 1' }}</small></td><td>{{ $date }}</td><td class="numeric">{{ $score }}</td><td><x-prototype.badge tone="success">Completed</x-prototype.badge></td><td><a class="table-action" href="{{ $reviewUrl }}" aria-label="Review {{ $name }}">Review →</a></td></tr>@endforeach</tbody></table></div>
