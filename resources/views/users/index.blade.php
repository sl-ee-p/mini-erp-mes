<x-layouts.app title="Users">
    <x-page-header title="Users" subtitle="Accounts and roles for the workshop team.">
        <x-slot:actions>
            <x-button :href="route('users.create')">New user</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="Search name or email…" class="w-64" />
        <x-form.select name="role" :options="$roles" :value="request('role')" placeholder="All roles" class="w-48" />
        <x-button variant="secondary">Filter</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </x-slot:head>
        @forelse ($users as $user)
            <tr>
                <td class="px-4 py-3 font-medium text-stone-900">{{ $user->name }}</td>
                <td class="px-4 py-3">{{ $user->email }}</td>
                <td class="px-4 py-3"><x-badge :color="$user->role->color()">{{ $user->role->label() }}</x-badge></td>
                <td class="px-4 py-3">
                    @if ($user->is_active)
                        <x-badge color="emerald">Active</x-badge>
                    @else
                        <x-badge>Inactive</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('users.edit', $user) }}" class="font-medium text-brand-700 hover:underline">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No users found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $users->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
