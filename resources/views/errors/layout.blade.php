{{-- Self-contained on purpose: no Vite assets or session data, so it still renders when those are what failed. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f5f5f4; color: #292524; font: 15px/1.5 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        main { max-width: 26rem; margin: 1rem; padding: 2rem; text-align: center; background: #fff; border-radius: 0.75rem; box-shadow: 0 0 0 1px #e7e5e4; }
        .logo { display: inline-flex; width: 3rem; height: 3rem; align-items: center; justify-content: center; border-radius: 0.5rem; background: #b45f2a; color: #fff; font-weight: 700; }
        .code { margin: 1.25rem 0 0; font: 600 0.85rem ui-monospace, monospace; color: #964925; }
        h1 { margin: 0.25rem 0 0; font-size: 1.15rem; }
        p.message { margin: 0.5rem 0 1.5rem; color: #57534e; }
        a, button { display: inline-block; margin: 0 0.25rem; padding: 0.5rem 0.9rem; border-radius: 0.375rem; font-family: inherit; font-size: 0.875rem; font-weight: 600; text-decoration: none; cursor: pointer; }
        a { background: #b45f2a; color: #fff; border: 1px solid #b45f2a; }
        button { background: #fff; color: #44403c; border: 1px solid #d6d3d1; }
    </style>
</head>
<body>
    <main>
        <span class="logo" aria-hidden="true">OW</span>
        <p class="code">Error {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p class="message">{{ $message }}</p>
        <a href="/">Go to the start page</a>
        <button type="button" onclick="history.back()">Back</button>
    </main>
</body>
</html>
