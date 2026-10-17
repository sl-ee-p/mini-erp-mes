@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null])

@php($id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name)))

<div {{ $attributes->only('class')->class(['space-y-1']) }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           {{ $attributes->except('class')->class([
               'block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500',
               'border-red-400' => $errors->has($name),
           ]) }}>
    @if ($help)
        <p class="text-xs text-stone-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
