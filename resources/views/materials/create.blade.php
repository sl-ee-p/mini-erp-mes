<x-layouts.app title="New raw material">
    <x-page-header title="New raw material" />
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('materials.store') }}" class="space-y-6">
            @csrf
            @include('materials._form')
            <div class="flex gap-2">
                <x-button>Create material</x-button>
                <x-button variant="secondary" :href="route('materials.index')">Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
