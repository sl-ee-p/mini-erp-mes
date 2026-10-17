<x-layouts.app :title="'Edit '.$order->number">
    <x-page-header :title="'Edit '.$order->number" />
    <form method="POST" action="{{ route('sales-orders.update', $order) }}" class="max-w-5xl space-y-6">
        @csrf
        @method('PUT')
        @include('sales-orders._form')
        <div class="flex gap-2">
            <x-button>Save changes</x-button>
            <x-button variant="secondary" :href="route('sales-orders.show', $order)">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
