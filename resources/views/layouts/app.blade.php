<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('app.tagline') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => url('/'),
        'description' => __('app.tagline'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => config('app.name'),
        'url' => url('/'),
        'inLanguage' => 'fa-IR',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="bg-cream text-ink font-sans antialiased min-h-screen flex flex-col">

    <header class="site-header border-b border-broca-sand">
        <nav class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="brand-lockup text-xl font-black"><span class="brand-mark" aria-hidden="true">ب</span><span>{{ __('app.name') }}</span></a>
            <div class="site-nav-links flex items-center gap-3">
                <a href="{{ route('catalog') }}" class="px-3 py-1.5 rounded-md font-bold hover:bg-broca-sand focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">{{ __('app.nav_catalog') }}</a>
                <a href="{{ route('plans') }}" class="px-3 py-1.5 rounded-md font-bold hover:bg-broca-sand focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">{{ __('app.nav_plans') }}</a>
                @guest
                    <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-md font-bold hover:bg-broca-sand focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">{{ __('app.cta_login') }}</a>
                    <a href="{{ route('register') }}" class="nav-primary px-3 py-1.5 rounded-md bg-broca-accent text-white font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-ink">{{ __('app.cta_register') }}</a>
                @else
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-md font-bold hover:bg-broca-sand focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">{{ __('app.nav_dashboard') }}</a>
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-md font-bold hover:bg-broca-sand focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">{{ __('app.nav_admin') }}</a>
                    @endif
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-md border border-ink/30 font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">{{ __('app.nav_logout') }}</button>
                    </form>
                @endguest
            </div>
        </nav>
    </header>

    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-4xl mt-6 rounded-2xl border border-teal/40 bg-teal/10 px-5 py-3 text-sm font-bold text-teal" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mx-auto max-w-4xl mt-6 rounded-2xl border border-coral/40 bg-coral/10 px-5 py-3 text-sm font-bold text-coral" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-broca-sand mt-16">
        <div class="max-w-6xl mx-auto px-4 py-8 grid gap-4 text-sm text-broca-slate md:grid-cols-2">
            <p>{{ __('app.footer_disclaimer') }}</p>
            <nav class="flex flex-wrap gap-4 md:justify-end" aria-label="پیوندهای حقوقی">
                <a class="underline hover:text-ink" href="{{ route('legal.show', 'terms') }}">{{ __('app.legal_terms') }}</a>
                <a class="underline hover:text-ink" href="{{ route('legal.show', 'privacy') }}">{{ __('app.legal_privacy') }}</a>
                <a class="underline hover:text-ink" href="{{ route('legal.show', 'medical-disclaimer') }}">{{ __('app.legal_medical') }}</a>
                <a class="underline hover:text-ink" href="{{ route('legal.show', 'contact') }}">{{ __('app.legal_contact') }}</a>
            </nav>
        </div>
    </footer>

    {{-- PUSHED SCRIPTS RENDER HERE. Without this @stack, every @push('scripts')
          block (video player, flashcard reviewer) was silently dropped. --}}
    @stack('scripts')
</body>
</html>
