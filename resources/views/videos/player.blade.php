@if($embed = $video->youtubeUrl(embed: true))
    <div class="video-stage video-stage-embed">
        <iframe src="{{ $embed.'?enablejsapi=1&origin='.rawurlencode(request()->getSchemeAndHttpHost()) }}" title="{{ $video->title }}" loading="lazy"
            data-playback-url="{{ route('library.videos.playback', $video, false) }}"
            data-playback-token="{{ csrf_token() }}"
            referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>
    <p class="muted">If playback is unavailable, <a href="{{ $video->youtubeUrl() }}">watch on YouTube</a>.</p>
@else
    <div class="video-stage">
        <x-prototype.icon name="play"/>
        <h2>Video unavailable</h2>
        <p>This video cannot be played right now.</p>
    </div>
@endif
