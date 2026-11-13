@php
$items = match($template) {
    'paths' => [['2nd Baccalaureate','A complete path to exam confidence.','10 units · 54 lessons','G03','Free'],['1st Baccalaureate','Build a strong foundation.','8 units · Sample preview','G03','Free'],['General English','English for everyday situations.','6 units · Sample preview','G03','Free']],
    'level' => [['Units','Learn step by step through your course.','10 structured units','G04','Free'],['Exercises','Practice what you have learned.','Grammar, vocabulary, and more','G11','Free'],['Exams','Prepare with confidence.','Documents and interactive practice','G13','Free'],['Resources','Keep useful study material close.','Guides and worksheets','G16','Free'],['Videos','See the explanation in action.','Teacher-led lessons','G19','Free'],['Topic index','Find a concept quickly.','Browse by topic','G09','Free']],
    'units' => [['Unit 1 · Gifts of youth','Explore the potential of young people.','5 categories · 6 lessons','G05','In progress'],['Unit 2 · Humour','Language that brings people together.','5 categories · Sample preview','G05','Free'],['Unit 3 · Education','Learning that opens new possibilities.','5 categories · Sample preview','G05','Free']],
    'unit' => array_map(fn($name) => [$name,'Build your skills in the context of Gifts of youth.','2nd Baccalaureate · Unit 1','G06','Free'],\App\Support\Prototype::UNIT_CATEGORIES),
    'lessons' => [['The passive voice','Focus on an action and its result.','Grammar · 15 minutes','G07','In progress'],['The passive in different tenses','Put your understanding into context.','Grammar · Sample lesson preview','G07','Free']],
    'exercises' => [['Passive voice practice','Practise seven different question formats.','7 questions · 10 minutes','G12','Free'],['Grammar review','Consolidate the essentials.','12 questions · Sample preview','G12','Free']],
    'exams' => [['Unit 1 · Practice exam','Test your understanding of Gifts of youth.','Interactive · 30 minutes','G15','Free'],['National exam · Document','Review a sample exam paper and correction.','Document exam · Sample files','G14','Free']],
    'resources' => [['Passive voice study guide','A concise reference with worked examples.','Study guide · Document preview','G17','Free'],['Grammar revision worksheet','A little extra practice for your revision.','Worksheet · 100 points','G23','Points required',['destination' => 'grammar-worksheet']],['Complete revision pack','Structured revision for your exam.','Premium classification preview','G23','Premium',['destination' => 'revision-pack']]],
    'videos' => [['Passive voice · Video lesson','A clear, visual introduction to passive sentences.','Grammar · 08:24','G20','Free'],['Exam writing essentials','Make your next paragraph stronger.','Writing · Sample video preview','G20','Free']],
    'topics' => [['Passive voice','Lessons, practice, and study material.','Grammar · 5 learning resources','G10','Free'],['Reported speech','Learn to share what someone said.','Grammar · Sample topic preview','G10','Free'],['Writing a formal email','Organize your ideas clearly.','Writing · Sample topic preview','G10','Free']],
    default => [['The passive voice','Understand the form and when to use it.','Lesson · Unit 1 · Grammar','G07','Free'],['Passive voice practice','Put your understanding to work.','Exercise · 7 questions','G12','Free'],['Unit 1 · Practice exam','Review your understanding.','Exam · 30 minutes','G15','Free'],['Passive voice study guide','A handy reference for revision.','Resource · Study guide','G17','Free'],['Passive voice · Video lesson','Follow a visual explanation.','Video · 08:24','G20','Free'],['Passive voice · Topic','Explore related learning material.','Topic · Grammar','G10','Free']],
};
@endphp
@if($emptyCategory)
    <div class="empty-state">
        <x-prototype.icon name="book"/>
        <h2>No lessons yet</h2>
        <p>This category has no sample lessons. Explore another category in this unit.</p>
        <x-prototype.link to="G05">Back to unit categories</x-prototype.link>
    </div>
@else
@if($template === 'level')<div class="notice info"><x-prototype.icon name="book"/><div><strong>Your path to a confident Baccalaureate.</strong><p>Structured lessons across vocabulary, functions, reading, grammar, and writing.</p><x-prototype.link to="S02">Select this learning path</x-prototype.link></div></div>@endif
<form class="filter-bar" data-filter-form><div class="field grow"><label for="content-search">{{ $template === 'search' ? 'Search all learning content' : 'Find in '.$screen['title'] }}</label><input id="content-search" type="search" name="q" placeholder="Try passive voice" data-content-search value="{{ $template === 'search' ? request('q', '') : '' }}"></div><div class="field"><label for="content-type">{{ $template === 'search' ? 'Content type' : 'Access' }}</label><select id="content-type" data-content-type><option value="">All {{ $template === 'search' ? 'types' : 'content' }}</option>@if($template === 'search')@foreach(['Lesson','Exercise','Exam','Resource','Video','Topic'] as $type)<option>{{ $type }}</option>@endforeach @else<option>Free</option><option>Points required</option><option>Premium</option>@endif</select></div><button class="button button-primary" type="submit">Search</button></form>
<div class="row between results-meta"><span data-result-count>{{ count($items) }} sample results</span>@if($template === 'search')<small>Lessons, exercises, exams, resources, videos & topics</small>@else<small>2nd Baccalaureate · Sample catalogue</small>@endif</div>
<div class="cards {{ $template === 'search' ? 'two' : 'three' }}" data-filter-list>@foreach($items as $key => [$title,$copy,$meta,$target,$access])<x-prototype.card data-filter-item data-filter-type="{{ $template === 'search' ? explode(' · ', $meta)[0] : $access }}">
@if($template === 'videos')<div class="video-thumbnail"><x-prototype.icon name="play"/><span>English Atlas · Video preview</span></div>@else<span class="tile-icon"><x-prototype.icon :name="in_array($template,['exams','resources']) ? 'file' : 'book'"/></span>@endif
<div class="row between"><x-prototype.badge :tone="$access === 'In progress' ? 'info' : 'neutral'">{{ $access }}</x-prototype.badge>@if(in_array($access,['Points required','Premium']))<x-prototype.icon name="lock"/>@endif</div><h2 class="card-title">{{ $title }}</h2><p>{{ $copy }}</p><small>{{ $meta }}</small>
@if($template === 'unit' && $key !== 'grammar')
    <a class="button button-secondary" href="{{ $url($target, ['category' => $key]) }}">Explore <x-prototype.icon name="arrow"/></a>
@else
    <x-prototype.link :href="$url($target, $items[$key][5] ?? [])">{{ $access === 'Free' || $access === 'In progress' ? 'Explore' : 'View requirements' }} <x-prototype.icon name="arrow"/></x-prototype.link>
@endif
</x-prototype.card>@endforeach</div>
<div class="empty-state" data-empty-state hidden><x-prototype.icon name="search"/><h2>No matching content</h2><p>Try a different search or reset your filters.</p><button class="button button-secondary" data-reset-filters>Reset filters</button></div>
@if(in_array($template,['level','unit','lessons','topic']))<div class="notice info"><x-prototype.icon name="message"/><div><strong>Need a hand finding your next step?</strong><p>Ask your teacher or return to your learning dashboard.</p><div class="actions"><x-prototype.link to="S14">Ask teacher</x-prototype.link><x-prototype.link to="S01" variant="tertiary">Open dashboard</x-prototype.link></div></div></div>@endif
@endif
