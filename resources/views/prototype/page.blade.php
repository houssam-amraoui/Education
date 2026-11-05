@extends('prototype.layout')
@section('content')
@php($template = $screen['template'])
@if(!in_array($template,['home','dashboard','admin-home']))
<div class="page-heading"><div><span class="eyebrow">{{ $area === 'admin' ? 'Teaching workspace' : 'Your English journey' }}</span><h1>{{ $screen['title'] }}</h1><p>{{ $area === 'admin' ? 'Organize the next step in your students’ learning.' : 'Build your confidence, one step at a time.' }}</p></div>@if($area === 'student')<x-prototype.badge tone="info">2nd Baccalaureate</x-prototype.badge>@endif</div>
@endif
@if(in_array($template,['home','dashboard','admin-home']))
    @include('prototype.partials.overviews')
@elseif(in_array($template,['paths','level','units','unit','lessons','topics','topic','exercises','exams','resources','videos','search']))
    @include('prototype.partials.catalogue')
@elseif(in_array($template,['lesson','instructions','document-exam','resource','document','video','preparation']))
    @include('prototype.partials.learning')
@elseif(in_array($template,['attempt','results','history','progress','student']))
    @include('prototype.partials.activity')
@elseif(in_array($template,['messages','compose','conversation']))
    @include('prototype.partials.messages')
@elseif(in_array($template,['rewards','achievement','unlock','access']))
    @include('prototype.partials.rewards')
@elseif(in_array($template,['auth','profile','settings','selection']))
    @include('prototype.partials.account')
@elseif(in_array($template,['admin-list','admin-editor','question-editor','relationships','analytics']))
    @include('prototype.partials.admin')
@endif
@endsection
