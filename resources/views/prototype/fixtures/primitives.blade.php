<x-prototype.fixture-layout title="Component primitives">
    <x-prototype.card class="stack">
        <h2>Lesson guide</h2>
        <p>Open the sample guide in a modal or drawer.</p>
        <div class="actions">
            <button type="button" class="button button-primary" data-open-dialog="lesson-modal" aria-haspopup="dialog">Open modal</button>
            <button type="button" class="button button-secondary" data-open-dialog="lesson-drawer" aria-haspopup="dialog">Open drawer</button>
        </div>
    </x-prototype.card>

    <div class="stack min-w-0">
        <h2>Sample lessons</h2>
        <x-prototype.paginated-table id="lessons" caption="Sample lessons"
            :columns="['Lesson', 'Category', 'Duration']" :paginator="$paginator">
            @foreach ($paginator as [$lesson, $category, $minutes])
                <tr>
                    <td>{{ $lesson }}</td>
                    <td>{{ $category }}</td>
                    <td class="numeric">{{ $minutes }} min</td>
                </tr>
            @endforeach
        </x-prototype.paginated-table>
    </div>

    <x-prototype.dialog id="lesson-modal" title="Lesson guide">
        <p>Read the examples, then practise writing a sentence of your own.</p>
    </x-prototype.dialog>
    <x-prototype.dialog id="lesson-drawer" title="Lesson guide" variant="drawer">
        <p>Read the examples, then practise writing a sentence of your own.</p>
    </x-prototype.dialog>
</x-prototype.fixture-layout>
