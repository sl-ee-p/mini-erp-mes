<x-layouts.app title="Stock movements">
    <x-page-header title="Stock movements" subtitle="Complete ledger of every stock change." />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="Material SKU or name…" class="w-64" />
        <x-form.select name="type" :options="$types" :value="request('type')" placeholder="All types" class="w-44" />
        <x-form.input name="from" type="date" label="From" :value="request('from')" />
        <x-form.input name="to" type="date" label="To" :value="request('to')" />
        <x-button variant="secondary">Filter</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Material</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3 text-right">Balance</th>
                <th class="px-4 py-3">Reason</th>
                <th class="px-4 py-3">By</th>
            </tr>
        </x-slot:head>
        @include('stock-movements._rows', ['movements' => $movements, 'showMaterial' => true])
        <x-slot:footer>{{ $movements->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
