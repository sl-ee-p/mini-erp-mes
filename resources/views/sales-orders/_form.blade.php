@php
    $lines = old('lines', $order->exists
        ? $order->lines->map(fn ($line) => ['product_id' => $line->product_id, 'quantity' => $line->quantity, 'unit_price' => $line->unit_price])->all()
        : [['product_id' => '', 'quantity' => 1, 'unit_price' => '']]);
@endphp

<div class="space-y-6"
     x-data="{
        products: @js(collect($products)->keyBy('id')),
        lines: @js(array_values($lines)),
        price(line) {
            if (line.unit_price !== '' && line.unit_price !== null) return Number(line.unit_price);
            return this.products[line.product_id]?.price ?? 0;
        },
        get total() { return this.lines.reduce((sum, line) => sum + this.price(line) * Number(line.quantity || 0), 0) },
        money(value) { return '€ ' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
     }">
    <x-card title="Order">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.select name="customer_id" label="Customer" :options="$customers" :value="$order->customer_id" placeholder="Select customer…" required />
            <x-form.input name="due_date" type="date" label="Requested delivery date" :value="$order->due_date?->format('Y-m-d')" required />
            <x-form.textarea name="notes" label="Notes" :value="$order->notes" rows="2" class="sm:col-span-2" />
        </div>
    </x-card>

    <x-card title="Lines">
        <x-slot:headerActions>
            <x-button type="button" variant="secondary" size="sm" @click="lines.push({ product_id: '', quantity: 1, unit_price: '' })">Add line</x-button>
        </x-slot:headerActions>
        @error('lines') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="space-y-2">
            <div class="hidden gap-2 text-xs font-semibold uppercase text-stone-500 sm:flex">
                <span class="flex-1">Product</span><span class="w-24">Qty</span><span class="w-32">Unit price</span><span class="w-32 text-right">Amount</span><span class="w-6"></span>
            </div>
            <template x-for="(line, index) in lines" :key="index">
                <div class="flex flex-wrap items-center gap-2">
                    <select :name="`lines[${index}][product_id]`" x-model="line.product_id" required aria-label="Product"
                            class="block min-w-56 flex-1 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Select product…</option>
                        <template x-for="product in Object.values(products)" :key="product.id">
                            <option :value="product.id" x-text="product.label" :selected="product.id == line.product_id"></option>
                        </template>
                    </select>
                    <input type="number" min="1" step="1" :name="`lines[${index}][quantity]`" x-model="line.quantity" required aria-label="Quantity"
                           class="block w-24 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <input type="number" min="0" step="0.01" :name="`lines[${index}][unit_price]`" x-model="line.unit_price" aria-label="Unit price"
                           :placeholder="products[line.product_id]?.price ?? 'List price'"
                           class="block w-32 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <span class="w-32 text-right text-sm tabular-nums" x-text="money(price(line) * Number(line.quantity || 0))"></span>
                    <button type="button" class="w-6 text-stone-400 hover:text-red-600" @click="lines.splice(index, 1)" x-show="lines.length > 1" aria-label="Remove line">&times;</button>
                </div>
            </template>
        </div>
        @foreach ($errors->get('lines.*') as $messages)
            <p class="mt-1 text-xs text-red-600">{{ $messages[0] }}</p>
        @endforeach

        <div class="mt-4 flex justify-end border-t border-stone-200 pt-4 text-sm">
            <span class="mr-4 text-stone-500">Total (excl. VAT)</span>
            <span class="w-32 text-right font-semibold tabular-nums" x-text="money(total)"></span>
            <span class="w-6"></span>
        </div>
        <p class="mt-2 text-xs text-stone-500">Leave unit price empty to use the product list price.</p>
    </x-card>
</div>
