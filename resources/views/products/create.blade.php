<x-layouts.app title="New product">
    <x-page-header title="New product" />
    <form method="POST" action="{{ route('products.store') }}" class="max-w-5xl space-y-6">
        @csrf
        @include('products._form')
        <div class="flex gap-2">
            <x-button>Create product</x-button>
            <x-button variant="secondary" :href="route('products.index')">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
