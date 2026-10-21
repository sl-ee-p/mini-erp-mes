<div class="grid gap-4 sm:grid-cols-2">
    <x-form.input name="code" label="Customer code" :value="$customer->code" required maxlength="20" />
    <x-form.input name="name" label="Company / name" :value="$customer->name" required />
    <x-form.input name="email" type="email" label="Email" :value="$customer->email" />
    <x-form.input name="phone" label="Phone" :value="$customer->phone" />
    <x-form.input name="vat_number" label="VAT number" :value="$customer->vat_number" />
    <x-form.input name="address" label="Address" :value="$customer->address" />
    <x-form.input name="city" label="City" :value="$customer->city" />
    <x-form.input name="country" label="Country (ISO code)" :value="$customer->country" required maxlength="2" />
    <x-form.textarea name="notes" label="Notes" :value="$customer->notes" rows="3" class="sm:col-span-2" />
</div>
