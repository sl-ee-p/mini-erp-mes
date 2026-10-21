@props(['name', 'label', 'checked' => false])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $invalid = $errors->has($name);
@endphp

<div {{ $attributes->only('class') }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="inline-flex items-center gap-2 text-sm text-stone-700">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
               @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
               class="rounded border-stone-300 text-brand-600 shadow-sm focus:ring-brand-500">
        {{ $label }}
    </label>
    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
