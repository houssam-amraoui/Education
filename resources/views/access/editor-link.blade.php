@if($accessRecord->exists && isset(App\Support\AccessTarget::MODELS[$accessRecord->getMorphClass()]))
    @can('access-rules.manage')
        <x-prototype.link :href="route('admin.access-rules.index', ['type' => $accessRecord->getMorphClass(), 'target' => $accessRecord->getKey()])" variant="tertiary">Configure access →</x-prototype.link>
    @endcan
@endif
