<x-layouts.app title="Customers">
    <x-page-header title="Customers" subtitle="Retailers, interior designers and private clients.">
        <x-slot:actions>
            @can('create', App\Models\Customer::class)
                <x-button :href="route('customers.create')">New customer</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="Search name, code, email, city…" class="w-72" />
        <x-button variant="secondary">Search</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Code</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">City</th>
                <th class="px-4 py-3">Country</th>
            </tr>
        </x-slot:head>
        @forelse ($customers as $customer)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3 font-mono text-xs">{{ $customer->code }}</td>
                <td class="px-4 py-3"><a href="{{ route('customers.show', $customer) }}" class="font-medium text-brand-700 hover:underline">{{ $customer->name }}</a></td>
                <td class="px-4 py-3">{{ $customer->email }}</td>
                <td class="px-4 py-3">{{ $customer->city }}</td>
                <td class="px-4 py-3">{{ $customer->country }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No customers found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $customers->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
