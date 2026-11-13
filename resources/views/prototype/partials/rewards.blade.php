@if($template === 'rewards')
<div class="stat-grid compact"><div class="stat"><x-prototype.icon name="award"/><strong>340 pts</strong><span>Your sample balance</span></div><div class="stat"><x-prototype.icon name="clock"/><strong>7 days</strong><span>Your learning streak</span></div><div class="stat"><x-prototype.icon name="check"/><strong>3 badges</strong><span>Small steps worth celebrating</span></div></div><div class="section-heading"><h2>Every step counts</h2></div><div class="cards three">@foreach([['Consistent learner','Learn for seven days in a row.','Earned'],['First steps','Complete your first lesson.','Earned'],['Practice makes progress','Complete five exercises.','Earned']] as [$name,$copy,$state])<x-prototype.card class="reward-card"><span class="achievement-icon"><x-prototype.icon name="award"/></span><x-prototype.badge tone="success">{{ $state }} · Sample</x-prototype.badge><h3>{{ $name }}</h3><p>{{ $copy }}</p><x-prototype.link to="S17">View achievement</x-prototype.link></x-prototype.card>@endforeach</div><div class="section-heading"><h2>Put your points to good use</h2></div><x-prototype.card><h3>Grammar revision worksheet</h3><p>A little more practice, at your own pace.</p><x-prototype.badge>100 points required</x-prototype.badge><x-prototype.link to="S18">View unlock preview</x-prototype.link></x-prototype.card>
@elseif($template === 'achievement')
<x-prototype.card class="reading achievement-detail" lang="en" dir="ltr"><span class="achievement-icon"><x-prototype.icon name="award"/></span><x-prototype.badge tone="success">Earned · Sample achievement</x-prototype.badge><h2>You kept showing up.</h2><p>Seven days of learning, one step at a time. Your consistency is building something good.</p><x-prototype.progress :value="100" label="7 of 7 learning days"/><p>Sample reward: 50 points · Earned 29 Oct 2026</p><div class="actions"><x-prototype.link to="G07" variant="primary">Keep learning</x-prototype.link><x-prototype.link to="S16">All achievements</x-prototype.link></div></x-prototype.card>
@elseif($template === 'unlock')
<x-prototype.card class="reading" lang="en" dir="ltr"><span class="tile-icon"><x-prototype.icon name="lock"/></span><h2>Grammar revision worksheet</h2><p>Use points to access a little extra revision. This action is a simulated outcome only.</p><dl class="details"><div><dt>Sample balance</dt><dd data-balance>340 points</dd></div><div><dt>Unlock cost</dt><dd>100 points</dd></div><div><dt>Balance after unlock</dt><dd data-balance-after>240 points · Preview</dd></div></dl><div class="actions"><button class="button button-primary" data-unlock>Preview unlock for 100 points</button><button class="button button-secondary" data-insufficient>Preview insufficient balance</button><x-prototype.link to="S16" variant="tertiary">Cancel</x-prototype.link></div><p data-feedback role="status" hidden></p><div data-unlocked hidden><x-prototype.link to="G17" variant="primary">Open sample resource</x-prototype.link></div></x-prototype.card>
@elseif($accessPreview)
    <x-prototype.card class="reading" lang="en" dir="ltr">
        <span class="tile-icon"><x-prototype.icon name="lock"/></span>
        <h2>{{ $accessPreview['title'] }}</h2>
        <div class="notice info">
            <div>
                <strong>{{ $accessPreview['requirement'] }}</strong>
                <p>{{ $accessPreview['description'] }}</p>
            </div>
        </div>
        <div class="actions">
            @if($accessPreview['action'])
                @php([$action, $label, $parameters] = $accessPreview['action'])
                <x-prototype.link :href="$url($action, $parameters)" variant="primary">{{ $label }}</x-prototype.link>
            @endif
            <x-prototype.link :to="$accessPreview['back'][0]">{{ $accessPreview['back'][1] }}</x-prototype.link>
        </div>
    </x-prototype.card>
@else
    <x-prototype.card class="reading" lang="en" dir="ltr">
        <span class="tile-icon"><x-prototype.icon name="lock"/></span>
        <h2>A little context before you continue.</h2>
        <p>Content may have different access requirements. Explore the sample states below.</p>
        <div class="stack">
            @foreach(\App\Support\Prototype::ACCESS_PREVIEWS as $destination => $preview)
                <div class="list-row">
                    <div>
                        <h3>{{ $preview['requirement'] }}</h3>
                        <p>{{ $preview['title'] }}</p>
                    </div>
                    <x-prototype.link :href="$url('G23', ['destination' => $destination])">View {{ strtolower($preview['requirement']) }} preview</x-prototype.link>
                </div>
            @endforeach
        </div>
        <x-prototype.link to="G16">Back to resources</x-prototype.link>
    </x-prototype.card>
@endif
