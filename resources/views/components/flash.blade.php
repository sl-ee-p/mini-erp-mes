{{-- Success messages fade after a few seconds; errors stay until dismissed, so they are not missed. --}}
@foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-red-200 bg-red-50 text-red-800'] as $key => $classes)
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" @if ($key === 'success') x-init="setTimeout(() => show = false, 6000)" @endif
             class="mb-6 flex items-start justify-between rounded-lg border px-4 py-3 text-sm {{ $classes }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">
            <span>{{ session($key) }}</span>
            <button type="button" class="ml-4 opacity-60 hover:opacity-100" @click="show = false" aria-label="Dismiss">&times;</button>
        </div>
    @endif
@endforeach
