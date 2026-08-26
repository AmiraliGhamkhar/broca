<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('app.tagline') }}">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-broca-cream text-broca-ink font-sans antialiased min-h-screen flex flex-col">

    <header class="border-b border-broca-sand">
        <nav class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-bold">{{ __('app.name') }}</a>
            <div class="flex gap-3">
                <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-md hover:bg-broca-sand">{{ __('app.cta_login') }}</a>
                <a href="{{ route('register') }}" class="px-3 py-1.5 rounded-md bg-broca-accent text-white">{{ __('app.cta_register') }}</a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-broca-sand mt-16">
        <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-broca-slate">
            {{ __('app.footer_disclaimer') }}
        </div>
    </footer>

</body>
</html>
