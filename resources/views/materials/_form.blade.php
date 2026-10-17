@php($isFinished = $material->exists && $material->type === App\Domain\Inventory\Enums\MaterialType::Finished)

@if ($isFinished)
    <p class="mb-4 rounded-md bg-violet-50 p-3 text-sm text-violet-800">
        This is the finished-good stock item of a product. SKU, name and unit are managed from the product.
    </p>
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <x-form.input name="sku" label="SKU" :value="$material->sku" required :readonly="$isFinished" />
    <x-form.input name="name" label="Name" :value="$material->name" required :readonly="$isFinished" />
    <x-form.select name="unit" label="Unit of measure" :options="$units" :value="$material->unit" required :disabled="$isFinished" />
    @if ($isFinished)
        <input type="hidden" name="unit" value="{{ $material->unit->value }}">
    @endif
    <x-form.input name="unit_cost" type="number" step="0.01" min="0" label="Unit cost (€)" :value="$material->unit_cost ?? 0" required />
    <x-form.input name="min_stock" type="number" step="0.001" min="0" label="Minimum stock" :value="$material->min_stock ?? 0" required
                  help="A low-stock alert is raised when the balance drops below this level. 0 disables alerts." />
</div>
