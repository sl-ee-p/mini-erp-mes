@php($user ??= null)

<div class="grid gap-4 sm:grid-cols-2">
    <x-form.input name="name" label="Name" :value="$user?->name" required />
    <x-form.input name="email" type="email" label="Email" :value="$user?->email" required />
    <x-form.select name="role" label="Role" :options="$roles" :value="$user?->role" required />
    @if ($user)
        <x-form.checkbox name="is_active" label="Active (can sign in)" :checked="$user->is_active" class="self-end pb-2" />
    @endif
    <x-form.input name="password" type="password" label="Password" autocomplete="new-password"
                  :help="$user ? 'Leave blank to keep the current password.' : null" :required="! $user" />
    <x-form.input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" />
</div>
