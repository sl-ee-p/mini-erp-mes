@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $invalid = $errors->has($name);
    $describedBy = collect([$attributes->get('aria-describedby'), $help ? $id.'-help' : null, $invalid ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div {{ $attributes->only('class')->class(['space-y-1']) }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if (! $label && $attributes->has('placeholder') && ! $attributes->has('aria-label')) aria-label="{{ $attributes->get('placeholder') }}" @endif
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @if ($invalid) aria-invalid="true" @endif
           @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
           {{ $attributes->except(['class', 'id', 'aria-describedby'])->class([
               'block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500',
               'border-red-400' => $invalid,
           ]) }}>
    @if ($help)
        <p id="{{ $id }}-help" class="text-xs text-stone-500">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
