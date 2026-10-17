@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null])

@php($id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name)))
@php($selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value))

<div {{ $attributes->only('class')->class(['space-y-1']) }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-stone-700">{{ $label }}</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}"
            {{ $attributes->except('class')->class([
                'block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500',
                'border-red-400' => $errors->has($name),
            ]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
