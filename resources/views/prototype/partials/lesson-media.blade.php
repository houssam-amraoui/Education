<section aria-labelledby="lesson-media-heading">
    <h2 id="lesson-media-heading">See the passive voice in action</h2>
    <figure>
        <img class="block w-full h-auto rounded-xl" src="{{ asset('prototype/passive-voice-example.png') }}"
            width="960" height="540" loading="lazy"
            alt="In “Students organize the event,” students are the subject. In “The event is organized by students,” the event is the subject.">
        <figcaption class="muted">In the passive sentence, the event becomes the subject.</figcaption>
    </figure>

    <h3 id="lesson-video-heading" class="mt-6">Watch an example</h3>
    <video class="block w-full aspect-video rounded-xl" controls playsinline preload="none"
        poster="{{ asset('prototype/passive-voice-poster.png') }}" aria-labelledby="lesson-video-heading"
        aria-describedby="lesson-video-description" width="960" height="540">
        <source src="{{ asset('prototype/passive-voice.webm') }}" type="video/webm">
    </video>
    <p id="lesson-video-description" class="muted">Silent walkthrough with on-screen examples.</p>
    <details class="card">
        <summary>Read the video steps</summary>
        <ol>
            <li>Start with “Students organize the event.” The object is “the event.”</li>
            <li>Move “the event” to the front so it becomes the subject.</li>
            <li>Add “is organized” to make “The event is organized.” Add “by students” if it matters who did it.</li>
        </ol>
    </details>

    <h3 class="mt-6">Study guide</h3>
    <div class="actions">
        <x-prototype.link to="G18">Open the study guide</x-prototype.link>
        <a class="button button-secondary" href="{{ asset('prototype/study-guide.txt') }}" download>Download sample text</a>
    </div>
</section>
