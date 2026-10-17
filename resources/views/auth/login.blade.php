<x-layouts.guest title="Sign in">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Email" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />
        <x-form.checkbox name="remember" label="Remember me" />
        <x-button class="w-full">Sign in</x-button>
    </form>

    @if (app()->environment('local'))
        <div class="mt-6 rounded-md bg-stone-50 p-3 text-xs text-stone-600">
            <p class="font-semibold">Demo accounts (password: <code>password</code>)</p>
            <p>admin@oakline.test · planner@oakline.test · operator@oakline.test · sales@oakline.test</p>
        </div>
    @endif
</x-layouts.guest>
