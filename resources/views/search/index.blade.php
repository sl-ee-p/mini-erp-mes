<x-layouts.app title="Search">
    <x-page-header title="Search" :subtitle="$term === '' ? 'Find orders, customers, products and materials.' : $total.' '.str('result')->plural($total).' for “'.$term.'”'" />

    <form method="GET" action="{{ route('search') }}" class="mb-6 flex max-w-xl gap-2" role="search" aria-label="Search page">
        <label for="search-page-q" class="sr-only">Search</label>
        <input id="search-page-q" type="search" name="q" value="{{ $term }}" autofocus placeholder="Order number, customer, SKU…"
               class="block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
        <x-button>Search</x-button>
    </form>

    @if ($term !== '' && mb_strlen($term) < App\Http\Controllers\Web\SearchController::MIN_LENGTH)
        <p class="text-sm text-stone-600">Type at least {{ App\Http\Controllers\Web\SearchController::MIN_LENGTH }} characters.</p>
    @elseif ($term !== '' && $groups === [])
        <div class="rounded-xl border-2 border-dashed border-stone-300 p-10 text-center text-stone-600">Nothing matches “{{ $term }}”.</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($groups as $label => $results)
            <x-card :title="$label" :padding="false">
                <ul class="divide-y divide-stone-100 text-sm">
                    @foreach ($results as $result)
                        <li>
                            <a href="{{ $result['url'] }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-stone-50 focus:bg-stone-50 focus:outline-none">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-brand-700">{{ $result['title'] }}</span>
                                    <span class="block truncate text-xs text-stone-500">{{ $result['meta'] }}</span>
                                </span>
                                @if ($result['badge'])
                                    <x-badge :color="$result['badge'][1]" class="shrink-0">{{ $result['badge'][0] }}</x-badge>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endforeach
    </div>
</x-layouts.app>
