<div class="space-y-1">
    @can('view-dashboard')
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
    @endcan
    @can('use-shop-floor')
        <x-nav-link :href="route('shop-floor.index')" :active="request()->routeIs('shop-floor.*')">Shop floor</x-nav-link>
    @endcan
</div>

@can('viewAny', App\Models\User::class)
    <div class="space-y-1">
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-stone-500">Administration</p>
        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">Users</x-nav-link>
    </div>
@endcan
