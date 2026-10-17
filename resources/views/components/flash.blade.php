@foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-red-200 bg-red-50 text-red-800'] as $key => $classes)
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="mb-6 flex items-start justify-between rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="status">
            <span>{{ session($key) }}</span>
            <button type="button" class="ml-4 opacity-60 hover:opacity-100" @click="show = false" aria-label="Dismiss">&times;</button>
        </div>
    @endif
@endforeach
