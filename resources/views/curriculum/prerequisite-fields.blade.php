@php($prerequisiteChoices = array_map('strval', array_filter((array) old('prerequisite_lesson_ids', $selectedPrerequisites), 'is_scalar')))
<fieldset id="prerequisite_lesson_ids">
    <legend>Prerequisite lessons</legend>
    <input type="hidden" name="prerequisite_lesson_ids" value="">
    @forelse($prerequisiteCandidates as $candidate)
        @php($archived = $candidate->status === App\Support\CurriculumStatus::Archived)
        <label class="choice">
            <input type="checkbox" name="prerequisite_lesson_ids[]" value="{{ $candidate->id }}"
                @checked(in_array((string) $candidate->id, $prerequisiteChoices, true)) @disabled($archived)>
            <span><bdi>{{ $candidate->title }}</bdi><br>
                <small><bdi>{{ $candidate->category->unit->level->title }} · {{ $candidate->category->unit->title }} · {{ $candidate->category->title }}</bdi>
                    @if($archived) · Archived (removed on save) @endif</small></span>
        </label>
    @empty
        <p>No lessons are available to link.</p>
    @endforelse
</fieldset>
