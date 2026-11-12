<form method="post" class="stack" action="{{ $prerequisiteAction }}" data-live-form>
    @csrf
    @method('PUT')
    @include('curriculum.prerequisite-fields')
    <button class="button button-secondary" data-pending="Saving…">Save prerequisites</button>
</form>
