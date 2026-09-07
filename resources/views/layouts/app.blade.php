<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', __('app.tagline') . ' — پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی')">
    @hasSection('meta_author')
        <meta name="author" content="@yield('meta_author')">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta name="theme-color" content="#222222">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ __('app.name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', __('app.name') . ' — ' . __('app.tagline'))">
    <meta property="og:description" content="@yield('meta_description', __('app.tagline') . ' — پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:image" content="@yield('og_image', url('/images/og-default.png'))">
    <meta property="og:image:alt" content="@yield('og_image_alt', 'بروکا — پلتفرم آموزش علوم پزشکی')">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @hasSection('article_published_time')
        <meta property="article:published_time" content="@yield('article_published_time')">
    @endif
    @hasSection('article_modified_time')
        <meta property="article:modified_time" content="@yield('article_modified_time')">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __('app.name') . ' — ' . __('app.tagline'))">
    <meta name="twitter:description" content="@yield('meta_description', __('app.tagline') . ' — پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی')">
    <meta name="twitter:image" content="@yield('og_image', url('/images/og-default.png'))">
    <title>@yield('title', __('app.name') . ' — ' . __('app.tagline'))</title>

    @if (! empty($markdownAlternate ?? null))
        <!-- Agent hint: this page has a clean Markdown twin (RFC 7763 MIME).
             Crawlers that read the DOM pick the <link>; headless fetchers
             pick the Link response header; a rendered-text reader (e.g. a
             user pasting the URL into a chatbot) picks the hidden line. -->
        <link rel="alternate" type="text/markdown" href="{{ $markdownAlternate }}">
    @endif

    <!-- LCP-critical fonts: the body (Vazirmatn Regular) and the display
         face used by above-the-fold headings (Lalezar). Preloading them
         avoids the CSS→font round trip on the hero. -->
    <link rel="preload" as="font" type="font/woff2" crossorigin href="/fonts/vazirmatn/Vazirmatn-Regular.woff2">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="/fonts/lalezar/Lalezar-Regular.woff2">

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Broca',
        'alternateName' => 'بروکا',
        'url' => url('/'),
        'description' => 'پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی برای دانشجویان؛ دوره‌ها با بازبینی علمی، مرور فاصله‌دار و ارزیابی.',
        'inLanguage' => 'fa-IR',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Broca',
        'alternateName' => 'بروکا',
        'url' => url('/'),
        'inLanguage' => 'fa-IR',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('catalog').'?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
</head>
<body class="bg-canvas text-ink font-sans antialiased min-h-screen flex flex-col" x-data="{ mobileNav: false }">

    @if (! empty($markdownAlternate ?? null))
        <div class="sr-only" aria-hidden="true">نسخهٔ مارک‌داون این صفحه در آدرس {{ $markdownAlternate }} در دسترس است، بهینه‌شده برای ابزارهای هوش مصنوعی و LLM.</div>
    @endif


    <!-- Main Navigation Bar -->
    <header class="site-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">

                <!-- Brand Logo -->
                <div class="flex items-center gap-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none">
                        <?php $brandLogoUrl = \App\Support\BrandAssets::logoUrl(); ?>
                        @if ($brandLogoUrl !== null)
                            <img src="{{ $brandLogoUrl }}" alt="{{ __('app.name') }}" class="h-10 w-auto max-w-[10rem] object-contain">
                        @else
                            <span class="brand-mark">ب</span>
                        @endif
                        <div>
                            <span class="font-display text-2xl leading-none text-ink block group-hover:text-rausch transition-colors">
                                {{ __('app.name') }}
                            </span>
                            <span class="sr-only">
                                آکادمی علوم پزشکی
                            </span>
                        </div>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden lg:flex items-center gap-1 text-xs font-bold" aria-label="ناوبری اصلی">
                        <a href="{{ route('catalog') }}"
                           class="px-4 py-2.5 rounded transition-all {{ request()->routeIs('catalog*') || request()->routeIs('courses*') ? 'bg-ink text-white' : 'text-ink hover:bg-surface-soft' }}">
                            {{ __('app.nav_catalog') }}
                        </a>
                        <a href="{{ route('plans') }}"
                           class="px-4 py-2.5 rounded transition-all {{ request()->routeIs('plans') ? 'bg-ink text-white' : 'text-ink hover:bg-surface-soft' }}">
                            {{ __('app.nav_plans') }}
                        </a>
                        <a href="{{ route('blog.index') }}"
                           class="px-4 py-2.5 rounded transition-all {{ request()->routeIs('blog*') ? 'bg-ink text-white' : 'text-ink hover:bg-surface-soft' }}">
                            مقالات و وبلاگ
                        </a>
                        <a href="{{ route('legal.show', 'medical-disclaimer') }}"
                           class="px-4 py-2.5 rounded transition-all {{ request()->is('medical-disclaimer') ? 'bg-ink text-white' : 'text-ink hover:bg-surface-soft' }}">
                            بیانیه علمی
                        </a>
                    </nav>
                </div>

                <!-- Search & User Actions -->
                <div class="flex items-center gap-3">
                    <!-- Quick Catalog Search (pill + Rausch orb) -->
                    <form method="get" action="{{ route('catalog') }}" class="hidden md:flex items-center relative">
                        <!-- Fixed width: the old focus:w-72 animated width,
                             which forces layout on every keystroke/focus.
                             Feedback now comes from border + shadow. -->
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجوی دوره، فیزیولوژی، آناتومی..."
                               class="w-56 lg:w-64 pl-10 pr-4 py-2.5 rounded border border-hairline bg-white text-xs font-medium focus:border-ink focus:shadow-float transition-[border-color,box-shadow] duration-200">
                        <button type="submit"
                                class="absolute left-1.5 top-1/2 -translate-y-1/2 size-8 rounded bg-rausch text-white text-xs grid place-items-center hover:bg-rausch-active transition-colors"
                                aria-label="جستجو">
                            <x-ui.icon name="search" class="size-4" />
                        </button>
                    </form>

                    @guest
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}"
                               class="px-4 py-2.5 rounded border border-hairline text-xs font-bold text-ink hover:bg-surface-soft transition-all">
                                {{ __('app.cta_login') }}
                            </a>
                            <a href="{{ route('register') }}"
                               class="px-5 py-2.5 rounded bg-rausch text-white text-xs font-bold hover:bg-rausch-active transition-all">
                                {{ __('app.cta_register') }}
                            </a>
                        </div>
                    @else
                        <!-- Authenticated Learner Menu -->
                        <div class="flex items-center gap-2" x-data="{ userMenu: false }">
                            <a href="{{ route('dashboard') }}"
                               class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded bg-surface-soft text-ink font-bold text-xs hover:bg-hairline-soft transition-all">
                                <x-ui.icon name="book" class="size-4" />
                                <span>{{ __('app.nav_dashboard') }}</span>
                            </a>

                            @if (auth()->user()->is_admin)
                                <a href="{{ route('admin.dashboard') }}"
                                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded bg-ink text-white font-bold text-xs hover:bg-rausch transition-all">
                                    <x-ui.icon name="shield" class="size-4" />
                                    <span>{{ __('app.nav_admin') }}</span>
                                </a>
                            @endif

                            <!-- User Dropdown Trigger -->
                            <div class="relative">
                                <button type="button" @click="userMenu = !userMenu"
                                        class="flex items-center gap-2 p-1.5 rounded border border-hairline bg-white hover:bg-surface-soft transition-all focus:outline-none"
                                        aria-haspopup="true" :aria-expanded="userMenu.toString()">
                                    <span class="grid size-8 place-items-center rounded-full bg-ink text-white text-xs font-bold">
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                    <span class="hidden md:block text-xs font-bold text-ink pl-2">
                                        {{ auth()->user()->name }}
                                    </span>
                                    <x-ui.icon name="chevron-down" class="size-3 text-muted" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="userMenu" @click.away="userMenu = false" x-cloak
                                     x-transition:enter="transition duration-150 ease-out"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition duration-100 ease-in"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0 -translate-y-1"
                                     class="absolute left-0 mt-2 w-64 rounded-lg bg-white border border-hairline-soft shadow-float py-2 text-xs font-bold z-50">
                                    <div class="px-4 py-2.5 border-b border-hairline-soft text-right">
                                        <span class="sr-only">{{ auth()->user()->name }} — {{ auth()->user()->email }}</span>
                                        <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[11px] font-bold {{ auth()->user()->hasActiveSubscription() ? 'bg-rausch-tint text-rausch-text' : 'bg-surface-soft text-muted' }}">
                                            {{ auth()->user()->hasActiveSubscription() ? 'اشتراک ویژه فعال ✓' : 'حساب رایگان' }}
                                        </span>
                                    </div>

                                    <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 hover:bg-surface-soft text-ink text-right">
                                        داشبورد و دوره‌های من
                                    </a>

                                    @if (auth()->user()->is_admin)
                                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 hover:bg-surface-soft text-rausch-text text-right font-bold">
                                            پنل مدیریت و استودیو
                                        </a>
                                    @endif

                                    <div class="border-t border-hairline-soft my-1"></div>

                                    <form method="post" action="{{ route('logout') }}" class="px-2">
                                        @csrf
                                        <button type="submit" class="w-full text-right px-3 py-2 rounded-xl text-rausch-text hover:bg-rausch-tint font-bold">
                                            خروج از حساب کاربری
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endguest

                    <!-- Mobile Menu Hamburger -->
                    <button type="button" @click="mobileNav = !mobileNav"
                            class="lg:hidden p-2.5 rounded border border-hairline text-ink hover:bg-surface-soft"
                            aria-label="باز کردن منو" :aria-expanded="mobileNav.toString()">
                        <x-ui.icon name="menu" class="size-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer Navigation -->
            <div x-show="mobileNav" x-cloak
                 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition duration-150 ease-in"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="lg:hidden pb-6 pt-2 border-t border-hairline-soft space-y-3">
                <form method="get" action="{{ route('catalog') }}" class="flex items-center">
                    <input type="text" name="q" placeholder="جستجوی دوره‌ها..."
                           class="w-full px-4 py-2.5 rounded border border-hairline bg-white text-xs">
                </form>

                <div class="grid grid-cols-2 gap-2 text-xs font-bold pt-2">
                    <a href="{{ route('catalog') }}" class="p-3 rounded-lg bg-surface-soft text-center border border-hairline-soft">
                        {{ __('app.nav_catalog') }}
                    </a>
                    <a href="{{ route('plans') }}" class="p-3 rounded-lg bg-surface-soft text-center border border-hairline-soft">
                        {{ __('app.nav_plans') }}
                    </a>
                    <a href="{{ route('blog.index') }}" class="p-3 rounded-lg bg-surface-soft text-center border border-hairline-soft">
                        وبلاگ مقالات
                    </a>
                    <a href="{{ route('legal.show', 'medical-disclaimer') }}" class="p-3 rounded-lg bg-surface-soft text-center border border-hairline-soft">
                        بیانیه علمی
                    </a>
                </div>

                @auth
                    <div class="pt-2 border-t border-hairline-soft flex flex-wrap gap-2">
                        <a href="{{ route('dashboard') }}" class="flex-1 py-2.5 rounded bg-surface-soft text-ink text-center font-bold text-xs">
                            داشبورد من
                        </a>
                        @if (auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="flex-1 py-2.5 rounded bg-ink text-white text-center font-bold text-xs">
                                پنل مدیریت
                            </a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Messages / Toast Feedback: Alpine-driven (no inline handlers),
         auto-dismiss after 7s, fades out so it never pops off the screen. -->
    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-4xl mt-6 px-4"
                 x-data="flashToast"
                 x-show="show" x-cloak
                 x-transition:enter="transition duration-300 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 -translate-y-2">
                <div class="rounded border border-hairline bg-white px-5 py-3 text-xs font-bold text-ink flex items-center justify-between shadow-float" role="status">
                    <span class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-teal inline-block"></span>
                        <span>{{ session('status') }}</span>
                    </span>
                    <button type="button" @click="dismiss" class="text-muted hover:text-ink transition-colors" aria-label="بستن پیام">
                        <x-ui.icon name="close" class="size-3 text-muted" />
                    </button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-auto max-w-4xl mt-6 px-4"
                 x-data="flashToast"
                 x-show="show" x-cloak
                 x-transition:enter="transition duration-300 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 -translate-y-2">
                <div class="rounded border border-error-text/30 bg-rausch-tint px-5 py-3 text-xs font-bold text-error-text flex items-center justify-between shadow-float" role="alert">
                    <span class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-error-text inline-block"></span>
                        <span>{{ session('error') }}</span>
                    </span>
                    <button type="button" @click="dismiss" class="text-error-text/70 hover:text-error-text transition-colors" aria-label="بستن پیام">
                        <x-ui.icon name="close" class="size-3 text-error-text" />
                    </button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-hairline-soft bg-white mt-20">
        <!-- Main Footer Links Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 text-xs">

                <!-- Col 1: Brand & Bio -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        @if (\App\Support\BrandAssets::logoUrl() !== null)
                            <img src="{{ \App\Support\BrandAssets::logoUrl() }}" alt="{{ __('app.name') }}" class="h-10 w-auto max-w-[10rem] object-contain">
                        @else
                            <span class="brand-mark">ب</span>
                        @endif
                        <div>
                            <span class="font-display text-xl text-ink">{{ __('app.name') }}</span>
                            <span class="text-[11px] text-muted block">آموزش عمیق علوم پزشکی برای دانشجویان</span>
                        </div>
                    </div>
                    <p class="text-muted leading-7 text-xs max-w-sm">
                        بروکا پلتفرم آموزش تخصصی علوم پایه، فیزیولوژی، آناتومی و نورولوژی با تکیه بر متدهای یادگیری فعال، مرور فاصله‌دار (Spaced Repetition) و بازبینی علمی دو مرحله‌ای پیش از انتشار است.
                    </p>
                </div>

                <!-- Col 2: Courses & Specialties — rendered from the real
                     subject table (short-cached): the old hard-coded slugs
                     (cardio/neuro/...) matched nothing in the database and
                     produced empty catalog pages. -->
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-ink">شاخه‌های آموزشی</h3>
                    <ul class="space-y-2.5 text-muted">
                        @php
                            $footerSubjects = \Illuminate\Support\Facades\Cache::remember(
                                'footer_subjects',
                                300,
                                // Cache plain arrays (slug + name), NOT Eloquent models:
                                // the database cache store unserializes with
                                // allowed_classes => false, which strips objects
                                // into useless strings on retrieval.
                                fn () => \App\Models\Subject::query()
                                    ->where('is_visible', true)
                                    ->orderBy('sort_order')
                                    ->limit(4)
                                    ->get(['slug', 'name'])
                                    ->map(fn ($s) => ['slug' => $s->slug, 'name' => $s->name])
                                    ->all()
                            );
                        @endphp
                        @forelse ($footerSubjects as $footerSubject)
                            <li><a href="{{ route('subjects.show', ['subject' => $footerSubject['slug']]) }}" class="hover:text-rausch transition-colors">{{ $footerSubject['name'] }}</a></li>
                        @empty
                            <li><a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors">دوره‌های آموزشی</a></li>
                        @endforelse
                        <li><a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors text-rausch font-bold">تمام دوره‌های آموزشی ←</a></li>
                    </ul>
                </div>

                <!-- Col 3: Learning Tools -->
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-ink">ابزارهای یادگیری</h3>
                    <ul class="space-y-2.5 text-muted">
                        <li><a href="{{ route('plans') }}" class="hover:text-rausch transition-colors">طرح‌های اشتراک و دسترسی ویژه</a></li>
                        <li><a href="{{ route('dashboard') }}" class="hover:text-rausch transition-colors">داشبورد و پیگیری پیشرفت</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors">جزوات و فایل‌های PDF اختصاصی</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors">فلش‌کارت‌های فاصله‌دار (SRS)</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors">آزمون‌های تشخیصی و سنجش</a></li>
                    </ul>
                </div>

                <!-- Col 4: Legal & Contact -->
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-ink">قوانین و پشتیبانی</h3>
                    <ul class="space-y-2.5 text-muted">
                        <li><a href="{{ route('legal.show', 'terms') }}" class="hover:text-rausch transition-colors">{{ __('app.legal_terms') }}</a></li>
                        <li><a href="{{ route('legal.show', 'privacy') }}" class="hover:text-rausch transition-colors">{{ __('app.legal_privacy') }}</a></li>
                        <li><a href="{{ route('legal.show', 'medical-disclaimer') }}" class="hover:text-rausch transition-colors">{{ __('app.legal_medical') }}</a></li>
                        <li><a href="{{ route('legal.show', 'contact') }}" class="hover:text-rausch transition-colors">{{ __('app.legal_contact') }}</a></li>
                    </ul>
                </div>
            </div>

            <!-- Legal Band -->
            <div class="mt-12 pt-6 border-t border-hairline-soft flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted">
                <p>© {{ date('Y') }} {{ __('app.name') }}. تمامی حقوق محفوظ است.</p>
                <p>{{ __('app.footer_disclaimer') }}</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
