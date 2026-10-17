<x-layouts.app :title="'Edit '.$material->name">
    <x-page-header :title="'Edit '.$material->name" />
    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('materials.update', $material) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('materials._form')
            <div class="flex gap-2">
                <x-button>Save changes</x-button>
                <x-button variant="secondary" :href="route('materials.show', $material)">Cancel</x-button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
