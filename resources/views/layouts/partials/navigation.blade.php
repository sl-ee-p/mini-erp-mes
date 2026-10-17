<div class="space-y-1">
    @can('view-dashboard')
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
    @endcan
    @can('use-shop-floor')
        <x-nav-link :href="route('shop-floor.index')" :active="request()->routeIs('shop-floor.*')">Shop floor</x-nav-link>
    @endcan
</div>

@can('viewAny', App\Models\Customer::class)
    <div class="space-y-1">
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-stone-500">Sales</p>
        <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">Customers</x-nav-link>
    </div>
@endcan

<div class="space-y-1">
    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-stone-500">Catalog &amp; inventory</p>
    <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">Products</x-nav-link>
    <x-nav-link :href="route('materials.index')" :active="request()->routeIs('materials.*')">
        <span class="flex-1">Materials</span>
        @php($lowStockCount = App\Models\Material::query()->belowMinimum()->count())
        @if ($lowStockCount > 0)
            <span class="rounded-full bg-red-500 px-2 text-xs font-semibold text-white" title="Below minimum stock">{{ $lowStockCount }}</span>
        @endif
    </x-nav-link>
    <x-nav-link :href="route('stock-movements.index')" :active="request()->routeIs('stock-movements.*')">Stock movements</x-nav-link>
</div>

@if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('view-audit-log'))
    <div class="space-y-1">
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-stone-500">Administration</p>
        @can('viewAny', App\Models\User::class)
            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">Users</x-nav-link>
        @endcan
        @can('view-audit-log')
            <x-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">Audit log</x-nav-link>
        @endcan
    </div>
@endif
