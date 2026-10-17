<x-layouts.app :title="'Edit '.$user->name">
    <x-page-header :title="'Edit '.$user->name" />
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('users._form', ['user' => $user])
            <div class="flex gap-2">
                <x-button>Save changes</x-button>
                <x-button variant="secondary" :href="route('users.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
