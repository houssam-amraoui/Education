@extends('prototype.layout')

@section('content')
    @include('curriculum.heading')
    <form method="post" action="{{ $submitUrl }}" class="reading stack" data-live-form>
        @csrf
        <div class="notice info">
            <x-prototype.icon name="message"/>
            <div><strong>To: Your English teacher</strong><p>Only student–teacher conversations are available.</p></div>
        </div>
        @if($context)
            <x-prototype.card>
                <span class="eyebrow">Attached learning context</span>
                <h3 dir="auto">{{ $context['title'] }}</h3>
                @if($context['context'])<p dir="auto">{{ implode(' · ', $context['context']) }}</p>@endif
                <a href="{{ $context['url'] }}">{{ $context['type'] === 'lesson' ? 'View source lesson' : 'View source content' }} →</a>
            </x-prototype.card>
        @endif
        @include('curriculum.feedback')
        @php($subject = $context ? Str::limit('Question about '.$context['title'], 160, '') : '')
        <x-prototype.field label="Subject" name="subject" required maxlength="160" dir="auto"
            :value="is_string(old('subject', $subject)) ? old('subject', $subject) : ''" :error="$errors->first('subject')"/>
        <x-prototype.field label="Your question" name="message" type="textarea" required maxlength="5000" dir="auto"
            help="Tell your teacher which part you would like help with."
            :value="is_string(old('message')) ? old('message') : ''" :error="$errors->first('message')"/>
        <div class="actions">
            <button class="button button-primary" data-pending="Sending…">Send question</button>
            <x-prototype.link :href="$url('S13')">Cancel</x-prototype.link>
        </div>
    </form>
@endsection
