@if(session('status'))
    <p class="notice success" role="status">{{ session('status') }}</p>
@endif
@if($errors->any())
    <div class="validation-summary" role="alert" tabindex="-1" data-live-errors>
        <strong>{{ __('shared.feedback.validation_summary') }}</strong>
        <ul>
            @foreach($errors->getMessages() as $field => $messages)
                @foreach($messages as $message)
                    <li>@if($panel === 'editor')<a href="#{{ $field }}">{{ $message }}</a>@else{{ $message }}@endif</li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
