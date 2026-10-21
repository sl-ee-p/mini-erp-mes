@props(['name', 'label' => null, 'value' => null, 'help' => null, 'rows' => 3])

@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $invalid = $errors->has($name);
    $describedBy = collect([$attributes->get('aria-describedby'), $help ? $id.'-help' : null, $invalid ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div {{ $attributes->only('class')->class(['space-y-1']) }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
              @if ($invalid) aria-invalid="true" @endif
              @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
              {{ $attributes->except(['class', 'id', 'aria-describedby'])->class([
                  'block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500',
                  'border-red-400' => $invalid,
              ]) }}>{{ old($name, $value) }}</textarea>
    @if ($help)
        <p id="{{ $id }}-help" class="text-xs text-stone-500">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $id }}-error" class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
