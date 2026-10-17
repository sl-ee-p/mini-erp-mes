<x-layouts.app title="New user">
    <x-page-header title="New user" />
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
            @csrf
            @include('users._form')
            <div class="flex gap-2">
                <x-button>Create user</x-button>
                <x-button variant="secondary" :href="route('users.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
