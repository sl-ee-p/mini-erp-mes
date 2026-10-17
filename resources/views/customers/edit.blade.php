<x-layouts.app :title="'Edit '.$customer->name">
    <x-page-header :title="'Edit '.$customer->name" />
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('customers._form')
            <div class="flex gap-2">
                <x-button>Save changes</x-button>
                <x-button variant="secondary" :href="route('customers.show', $customer)">Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
