<x-layouts.app title="Audit log">
    <x-page-header title="Audit log" subtitle="Who changed what, and when." />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.select name="type" :options="$types" :value="request('type')" placeholder="All records" class="w-48" />
        <x-form.select name="event" :options="$events" :value="request('event')" placeholder="All events" class="w-40" />
        <x-form.select name="user_id" :options="$users" :value="request('user_id')" placeholder="All users" class="w-48" />
        <x-button variant="secondary">Filter</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">When</th>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Event</th>
                <th class="px-4 py-3">Record</th>
                <th class="px-4 py-3">Changes</th>
            </tr>
        </x-slot:head>
        @forelse ($logs as $log)
            <tr class="align-top">
                <td class="whitespace-nowrap px-4 py-3 text-stone-500">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                <td class="px-4 py-3">
                    <x-badge :color="match ($log->event) { 'created' => 'emerald', 'deleted' => 'red', default => 'sky' }">{{ ucfirst($log->event) }}</x-badge>
                </td>
                <td class="px-4 py-3">{{ str($log->auditable_type)->headline() }} #{{ $log->auditable_id }}</td>
                <td class="px-4 py-3">
                    <dl class="space-y-0.5 text-xs">
                        @foreach (array_slice($log->changedAttributes(), 0, 8) as $attribute)
                            <div class="flex gap-2">
                                <dt class="font-medium text-stone-600">{{ $attribute }}:</dt>
                                <dd class="text-stone-500">
                                    @if ($log->event === 'updated')
                                        <span class="line-through">{{ json_encode($log->old_values[$attribute] ?? null) }}</span> →
                                    @endif
                                    <span class="text-stone-800">{{ json_encode($log->new_values[$attribute] ?? $log->old_values[$attribute] ?? null) }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">Nothing logged yet.</td></tr>
        @endforelse
        <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
