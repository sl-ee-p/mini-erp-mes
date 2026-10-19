@php
    $bom = old('bom', $product->exists
        ? $product->bomItems->map(fn ($item) => ['material_id' => $item->material_id, 'quantity' => $item->quantity])->all()
        : []);
    $routing = old('routing', $product->exists
        ? $product->routingSteps->map(fn ($step) => [
            'work_center_id' => $step->work_center_id,
            'name' => $step->name,
            'planned_minutes_per_unit' => $step->planned_minutes_per_unit,
            'consumes_materials' => $step->consumes_materials,
        ])->all()
        : [['work_center_id' => array_key_first($workCenters), 'name' => '', 'planned_minutes_per_unit' => '', 'consumes_materials' => true]]);
@endphp

<div class="space-y-6"
     x-data="{
        bom: @js(array_values($bom)),
        routing: @js(array_values($routing)),
        addBomLine() { this.bom.push({ material_id: '', quantity: '' }) },
        addStep() { this.routing.push({ work_center_id: @js(array_key_first($workCenters)), name: '', planned_minutes_per_unit: '', consumes_materials: false }) },
        move(index, offset) {
            const target = index + offset;
            if (target < 0 || target >= this.routing.length) return;
            [this.routing[index], this.routing[target]] = [this.routing[target], this.routing[index]];
        },
     }">
    <x-card title="Product">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="sku" label="SKU" :value="$product->sku" required />
            <x-form.input name="name" label="Name" :value="$product->name" required />
            <x-form.input name="price" type="number" step="0.01" min="0" label="List price (€)" :value="$product->price" required />
            <x-form.checkbox name="is_active" label="Active (available on sales orders)" :checked="$product->is_active" class="self-end pb-2" />
            <div class="space-y-1 sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-stone-700">Description</label>
                <textarea id="description" name="description" rows="2" class="block w-full rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>
    </x-card>

    <x-card title="Bill of materials (per unit)">
        <x-slot:headerActions>
            <x-button type="button" variant="secondary" size="sm" @click="addBomLine()">Add material</x-button>
        </x-slot:headerActions>
        @error('bom') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="space-y-2">
            <template x-for="(line, index) in bom" :key="index">
                <div class="flex items-start gap-2">
                    <select :name="`bom[${index}][material_id]`" x-model="line.material_id" required aria-label="Material"
                            class="block w-full flex-1 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">Select material…</option>
                        @foreach ($materials as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.001" min="0.001" :name="`bom[${index}][quantity]`" x-model="line.quantity" required placeholder="Qty" aria-label="Quantity per unit"
                           class="block w-28 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <button type="button" class="p-2 text-stone-400 hover:text-red-600" @click="bom.splice(index, 1)" aria-label="Remove line">&times;</button>
                </div>
            </template>
            <p x-show="bom.length === 0" class="text-sm text-stone-500">No materials yet.</p>
        </div>
        @foreach ($errors->get('bom.*') as $messages)
            <p class="mt-1 text-xs text-red-600">{{ $messages[0] }}</p>
        @endforeach
    </x-card>

    <x-card title="Routing">
        <x-slot:headerActions>
            <x-button type="button" variant="secondary" size="sm" @click="addStep()">Add step</x-button>
        </x-slot:headerActions>
        @error('routing') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror
        <div class="space-y-2">
            <template x-for="(step, index) in routing" :key="index">
                <div class="flex flex-wrap items-center gap-2 rounded-md bg-stone-50 p-2">
                    <span class="w-8 text-center text-xs font-semibold text-stone-500" x-text="(index + 1) * 10"></span>
                    <input type="text" :name="`routing[${index}][name]`" x-model="step.name" required placeholder="Step name" aria-label="Step name"
                           class="block min-w-40 flex-1 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <select :name="`routing[${index}][work_center_id]`" x-model="step.work_center_id" aria-label="Work center"
                            class="block w-40 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @foreach ($workCenters as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0.01" :name="`routing[${index}][planned_minutes_per_unit]`" x-model="step.planned_minutes_per_unit" required placeholder="min/unit" aria-label="Planned minutes per unit"
                           class="block w-28 rounded-md border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <input type="hidden" :name="`routing[${index}][consumes_materials]`" :value="step.consumes_materials ? 1 : 0">
                    <label class="inline-flex items-center gap-1 text-xs text-stone-600">
                        <input type="checkbox" x-model="step.consumes_materials" class="rounded border-stone-300 text-brand-600 focus:ring-brand-500"
                               @change="if (step.consumes_materials) routing.forEach((other, i) => { if (i !== index) other.consumes_materials = false })">
                        Consumes BOM
                    </label>
                    <div class="flex">
                        <button type="button" class="p-1 text-stone-400 hover:text-stone-700" @click="move(index, -1)" aria-label="Move up">&uarr;</button>
                        <button type="button" class="p-1 text-stone-400 hover:text-stone-700" @click="move(index, 1)" aria-label="Move down">&darr;</button>
                        <button type="button" class="p-1 text-stone-400 hover:text-red-600" @click="routing.splice(index, 1)" x-show="routing.length > 1" aria-label="Remove step">&times;</button>
                    </div>
                </div>
            </template>
        </div>
        <p class="mt-3 text-xs text-stone-500">The whole bill of materials is consumed from stock when the flagged step is completed. If no step is flagged, the first step consumes it.</p>
        @foreach ($errors->get('routing.*') as $messages)
            <p class="mt-1 text-xs text-red-600">{{ $messages[0] }}</p>
        @endforeach
    </x-card>
</div>
