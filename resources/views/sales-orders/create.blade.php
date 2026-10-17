<x-layouts.app title="New sales order">
    <x-page-header title="New sales order" subtitle="Orders are saved as drafts until confirmed." />
    <form method="POST" action="{{ route('sales-orders.store') }}" class="max-w-5xl space-y-6">
        @csrf
        @include('sales-orders._form')
        <div class="flex gap-2">
            <x-button>Save draft</x-button>
            <x-button variant="secondary" :href="route('sales-orders.index')">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
