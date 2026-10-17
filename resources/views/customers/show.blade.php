<x-layouts.app :title="$customer->name">
    <x-page-header :title="$customer->name" :subtitle="$customer->code">
        <x-slot:actions>
            @can('update', $customer)
                <x-button variant="secondary" :href="route('customers.edit', $customer)">Edit</x-button>
            @endcan
            @can('delete', $customer)
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger">Delete</x-button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Details">
            <dl class="space-y-3 text-sm">
                @foreach (['Email' => $customer->email, 'Phone' => $customer->phone, 'VAT number' => $customer->vat_number, 'Address' => trim($customer->address.', '.$customer->city.' ('.$customer->country.')', ', ')] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium uppercase text-stone-500">{{ $label }}</dt>
                        <dd>{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
                @if ($customer->notes)
                    <div>
                        <dt class="text-xs font-medium uppercase text-stone-500">Notes</dt>
                        <dd class="whitespace-pre-line">{{ $customer->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>
    </div>
</x-layouts.app>
