@forelse($recommendedLessons as $next)
    <a href="{{ App\Support\ProgressPages::lessonUrl($next) }}" dir="auto">{{ $next->title }}</a>
@empty
    <p>No recommendations right now.</p>
@endforelse
