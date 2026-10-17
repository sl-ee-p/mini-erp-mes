<x-layouts.app :title="'Edit '.$product->name">
    <x-page-header :title="'Edit '.$product->name" subtitle="Changes apply to new production orders only; orders already released keep their routing." />
    <form method="POST" action="{{ route('products.update', $product) }}" class="max-w-5xl space-y-6">
        @csrf
        @method('PUT')
        @include('products._form')
        <div class="flex gap-2">
            <x-button>Save changes</x-button>
            <x-button variant="secondary" :href="route('products.show', $product)">Cancel</x-button>
        </div>
    </form>
</x-layouts.app>
