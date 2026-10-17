<x-layouts.app title="New customer">
    <x-page-header title="New customer" />
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('customers.store') }}" class="space-y-6">
            @csrf
            @include('customers._form')
            <div class="flex gap-2">
                <x-button>Create customer</x-button>
                <x-button variant="secondary" :href="route('customers.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
