@props(['name', 'label', 'checked' => false])

<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="inline-flex items-center gap-2 text-sm text-stone-700">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
               class="rounded border-stone-300 text-brand-600 shadow-sm focus:ring-brand-500">
        {{ $label }}
    </label>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
