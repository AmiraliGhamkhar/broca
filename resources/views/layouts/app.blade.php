<!DOCTYPE html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('app.tagline') }} — پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <title>@yield('title', __('app.name') . ' — ' . __('app.tagline'))</title>

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
<body class="bg-cream text-ink font-sans antialiased min-h-screen flex flex-col selection:bg-sun selection:text-ink" x-data="{ mobileNav: false }">

    <!-- Top Announcement Bar -->
    <aside class="bg-ink text-cream/90 text-xs py-2 px-4 border-b border-ink/20" aria-label="اطلاعیه">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="inline-flex size-2 rounded-full bg-teal animate-pulse" aria-hidden="true"></span>
                <span class="font-bold">سامانه آموزش تخصصی پزشکی بروکا — دسترسی به دروس نمونه، جزوات و فلش‌کارت‌ها بدون هزینه فعال است.</span>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-[11px] text-cream/70">
                <span>تأییدیه علمی اعضای هیئت علمی</span>
                <span>•</span>
                <a href="{{ route('plans') }}" class="text-sun font-bold hover:underline">مشاهده تعرفه اشتراک‌ها ←</a>
            </div>
        </div>
    </aside>

    <!-- Main Navigation Bar -->
    <header class="site-header sticky top-0 z-50 bg-cream/90 backdrop-blur-md border-b border-broca-sand/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">

                <!-- Brand Logo -->
                <div class="flex items-center gap-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 group focus:outline-none">
                        <span class="grid size-11 place-items-center rounded-2xl bg-ink text-sun font-black text-xl shadow-md group-hover:bg-coral transition-colors duration-200">
                            ب
                        </span>
                        <div>
                            <span class="text-xl font-black tracking-tight text-ink block leading-tight group-hover:text-coral transition-colors">
                                {{ __('app.name') }}
                            </span>
                            <span class="text-[11px] font-bold text-broca-slate block">
                                آکادمی علوم پزشکی
                            </span>
                        </div>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden lg:flex items-center gap-1 text-xs font-black">
                        <a href="{{ route('catalog') }}"
                           class="px-4 py-2.5 rounded-full transition-all {{ request()->routeIs('catalog*') || request()->routeIs('courses*') ? 'bg-ink text-cream' : 'text-ink/80 hover:bg-broca-sand/70' }}">
                            {{ __('app.nav_catalog') }}
                        </a>
                        <a href="{{ route('plans') }}"
                           class="px-4 py-2.5 rounded-full transition-all {{ request()->routeIs('plans') ? 'bg-ink text-cream' : 'text-ink/80 hover:bg-broca-sand/70' }}">
                            {{ __('app.nav_plans') }}
                        </a>
                        <a href="{{ route('blog.index') }}"
                           class="px-4 py-2.5 rounded-full transition-all {{ request()->routeIs('blog*') ? 'bg-ink text-cream' : 'text-ink/80 hover:bg-broca-sand/70' }}">
                            مقالات و وبلاگ
                        </a>
                        <a href="{{ route('legal.show', 'medical-disclaimer') }}"
                           class="px-4 py-2.5 rounded-full transition-all {{ request()->is('medical-disclaimer') ? 'bg-ink text-cream' : 'text-ink/80 hover:bg-broca-sand/70' }}">
                            بیانیه علمی
                        </a>
                    </nav>
                </div>

                <!-- Search & User Actions -->
                <div class="flex items-center gap-3">
                    <!-- Quick Catalog Search form (compact) -->
                    <form method="get" action="{{ route('catalog') }}" class="hidden md:flex items-center relative">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجوی دوره، فیزیولوژی، آناتومی..."
                               class="w-56 lg:w-64 pl-8 pr-4 py-2 rounded-full border border-ink/15 bg-white/60 text-xs font-medium focus:w-72 focus:bg-white transition-all focus:border-coral focus:ring-1 focus:ring-coral">
                        <button type="submit" class="absolute left-2.5 text-broca-slate hover:text-coral text-xs" aria-label="جستجو">🔍</button>
                    </form>

                    @guest
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}"
                               class="px-4 py-2.5 rounded-full border border-ink/20 text-xs font-black text-ink hover:bg-broca-sand transition-all">
                                {{ __('app.cta_login') }}
                            </a>
                            <a href="{{ route('register') }}"
                               class="px-5 py-2.5 rounded-full bg-coral text-white text-xs font-black hover:bg-ink transition-all shadow-sm">
                                {{ __('app.cta_register') }}
                            </a>
                        </div>
                    @else
                        <!-- Authenticated Learner Menu -->
                        <div class="flex items-center gap-2" x-data="{ userMenu: false }">
                            <a href="{{ route('dashboard') }}"
                               class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full bg-sun text-ink font-black text-xs hover:bg-cream border border-ink/10 transition-all shadow-xs">
                                <span>📚</span>
                                <span>{{ __('app.nav_dashboard') }}</span>
                            </a>

                            @if (auth()->user()->is_admin)
                                <a href="{{ route('admin.dashboard') }}"
                                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-full bg-ink text-cream font-black text-xs hover:bg-coral transition-all">
                                    <span>⚙️</span>
                                    <span>{{ __('app.nav_admin') }}</span>
                                </a>
                            @endif

                            <!-- User Dropdown Trigger -->
                            <div class="relative">
                                <button type="button" @click="userMenu = !userMenu"
                                        class="flex items-center gap-2 p-1.5 rounded-full border border-ink/20 bg-white/70 hover:bg-white transition-all focus:outline-none">
                                    <span class="grid size-8 place-items-center rounded-full bg-ink text-cream text-xs font-black">
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                    <span class="hidden md:block text-xs font-black text-ink pl-2">
                                        {{ auth()->user()->name }}
                                    </span>
                                    <span class="text-[10px] text-broca-slate pl-1">▼</span>
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="userMenu" @click.away="userMenu = false" x-cloak
                                     class="absolute left-0 mt-2 w-56 rounded-2xl bg-white border border-broca-sand shadow-lg py-2 text-xs font-bold z-50">
                                    <div class="px-4 py-2 border-b border-broca-sand text-right">
                                        <p class="font-black text-ink">{{ auth()->user()->name }}</p>
                                        <p class="text-[11px] text-broca-slate font-mono" dir="ltr">{{ auth()->user()->email }}</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-black {{ auth()->user()->hasActiveSubscription() ? 'bg-teal/15 text-teal' : 'bg-broca-sand text-broca-slate' }}">
                                            {{ auth()->user()->hasActiveSubscription() ? 'اشتراک ویژه فعال ✓' : 'حساب رایگان' }}
                                        </span>
                                    </div>

                                    <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 hover:bg-broca-sand text-ink text-right">
                                        داشبورد و دوره‌های من
                                    </a>

                                    @if (auth()->user()->is_admin)
                                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 hover:bg-broca-sand text-coral text-right font-black">
                                            پنل مدیریت و استودیو
                                        </a>
                                    @endif

                                    <div class="border-t border-broca-sand my-1"></div>

                                    <form method="post" action="{{ route('logout') }}" class="px-2">
                                        @csrf
                                        <button type="submit" class="w-full text-right px-3 py-2 rounded-xl text-coral hover:bg-coral/10 font-black">
                                            خروج از حساب کاربری
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endguest

                    <!-- Mobile Menu Hamburger -->
                    <button type="button" @click="mobileNav = !mobileNav"
                            class="lg:hidden p-2 rounded-xl border border-ink/20 text-ink hover:bg-broca-sand"
                            aria-label="باز کردن منو">
                        <span class="text-xl leading-none">☰</span>
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer Navigation -->
            <div x-show="mobileNav" x-cloak class="lg:hidden pb-6 pt-2 border-t border-broca-sand space-y-3">
                <form method="get" action="{{ route('catalog') }}" class="flex items-center">
                    <input type="text" name="q" placeholder="جستجوی دوره‌ها..."
                           class="w-full px-4 py-2 rounded-xl border border-ink/20 bg-white text-xs">
                </form>

                <div class="grid grid-cols-2 gap-2 text-xs font-black pt-2">
                    <a href="{{ route('catalog') }}" class="p-3 rounded-xl bg-white/70 text-center border border-broca-sand">
                        {{ __('app.nav_catalog') }}
                    </a>
                    <a href="{{ route('plans') }}" class="p-3 rounded-xl bg-white/70 text-center border border-broca-sand">
                        {{ __('app.nav_plans') }}
                    </a>
                    <a href="{{ route('blog.index') }}" class="p-3 rounded-xl bg-white/70 text-center border border-broca-sand">
                        وبلاگ مقالات
                    </a>
                    <a href="{{ route('legal.show', 'medical-disclaimer') }}" class="p-3 rounded-xl bg-white/70 text-center border border-broca-sand">
                        بیانیه علمی
                    </a>
                </div>

                @auth
                    <div class="pt-2 border-t border-broca-sand flex flex-wrap gap-2">
                        <a href="{{ route('dashboard') }}" class="flex-1 py-2.5 rounded-xl bg-sun text-ink text-center font-black text-xs">
                            داشبورد من
                        </a>
                        @if (auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="flex-1 py-2.5 rounded-xl bg-ink text-cream text-center font-black text-xs">
                                پنل مدیریت
                            </a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Messages / Toast Feedback -->
    <main class="flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-4xl mt-6 px-4">
                <div class="rounded-2xl border border-teal/40 bg-teal/10 px-5 py-3.5 text-xs font-black text-teal flex items-center justify-between shadow-xs" role="status">
                    <span class="flex items-center gap-2">
                        <span class="text-base">✓</span>
                        <span>{{ session('status') }}</span>
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-teal/70 hover:text-teal">✕</button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="mx-auto max-w-4xl mt-6 px-4">
                <div class="rounded-2xl border border-coral/40 bg-coral/10 px-5 py-3.5 text-xs font-black text-coral flex items-center justify-between shadow-xs" role="alert">
                    <span class="flex items-center gap-2">
                        <span class="text-base">⚠️</span>
                        <span>{{ session('error') }}</span>
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-coral/70 hover:text-coral">✕</button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Luxury Airbnb-Style Persian Medical Footer -->
    <footer class="border-t border-broca-sand bg-white/60 mt-20">
        <!-- Medical Disclaimer Banner -->
        <div class="bg-sun/30 border-b border-broca-sand py-4 px-4">
            <div class="max-w-7xl mx-auto flex items-center gap-3 text-xs text-ink/80 leading-6">
                <span class="text-lg">⚖️</span>
                <p>
                    <strong class="text-ink font-black">بیانیه مسئولیت پزشکی:</strong>
                    محتوای آموزشی سامانه بروکا صرفاً برای ارتقای دانش دانشجویان و فراگیران علوم پزشکی تدوین شده و هیچ‌گونه توصیه، تشخیص یا درمان بالینی برای بیماران ارائه نمی‌دهد.
                </p>
            </div>
        </div>

        <!-- Main Footer Links Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 text-xs">

                <!-- Col 1: Brand & Bio -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-2xl bg-ink text-sun font-black text-lg">ب</span>
                        <div>
                            <span class="text-lg font-black text-ink">{{ __('app.name') }}</span>
                            <span class="text-[11px] text-broca-slate block">آموزش عمیق علوم پزشکی برای دانشجویان</span>
                        </div>
                    </div>
                    <p class="text-broca-slate leading-7 text-xs max-w-sm">
                        بروکا پلتفرم پیشرو در آموزش تخصصی علوم پایه، فیزیولوژی، آناتومی و نورولوژی با تکیه بر متدهای یادگیری فعال، مرور فاصله‌دار (Spaced Repetition) و نظارت اعضای هیئت علمی دانشگاه‌های علوم پزشکی است.
                    </p>
                    <div class="flex items-center gap-2 pt-2">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-teal/10 text-teal font-black text-[11px]">
                            ✓ تأییدیه بازبینی پزشکی (YMYL)
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-sun text-ink font-black text-[11px]">
                            الگوریتم یادگیری SM-2
                        </span>
                    </div>
                </div>

                <!-- Col 2: Courses & Specialties -->
                <div class="space-y-3">
                    <h3 class="text-sm font-black text-ink">شاخه‌های آموزشی</h3>
                    <ul class="space-y-2.5 text-broca-slate">
                        <li><a href="{{ route('catalog') }}?subject=cardio" class="hover:text-coral transition-colors">فیزیولوژی و الکتروفیزیولوژی قلب</a></li>
                        <li><a href="{{ route('catalog') }}?subject=neuro" class="hover:text-coral transition-colors">نوروآناتومی و ناحیه بروکا</a></li>
                        <li><a href="{{ route('catalog') }}?subject=anatomy" class="hover:text-coral transition-colors">آناتومی بالینی و اسکلتی قفسه سینه</a></li>
                        <li><a href="{{ route('catalog') }}?subject=physiology" class="hover:text-coral transition-colors">فیزیولوژی عمومی و سلولی</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-coral transition-colors text-coral font-bold">تمام دوره‌های آموزشی ←</a></li>
                    </ul>
                </div>

                <!-- Col 3: Learning Tools -->
                <div class="space-y-3">
                    <h3 class="text-sm font-black text-ink">ابزارهای یادگیری</h3>
                    <ul class="space-y-2.5 text-broca-slate">
                        <li><a href="{{ route('plans') }}" class="hover:text-coral transition-colors">طرح‌های اشتراک و دسترسی ویژه</a></li>
                        <li><a href="{{ route('dashboard') }}" class="hover:text-coral transition-colors">داشبورد و پیگیری پیشرفت</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-coral transition-colors">جزوات و فایل‌های PDF اختصاصی</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-coral transition-colors">فلش‌کارت‌های فاصله‌دار (SRS)</a></li>
                        <li><a href="{{ route('catalog') }}" class="hover:text-coral transition-colors">آزمون‌های تشخیصی و سنجش</a></li>
                    </ul>
                </div>

                <!-- Col 4: Legal & Contact -->
                <div class="space-y-3">
                    <h3 class="text-sm font-black text-ink">قوانین و پشتیبانی</h3>
                    <ul class="space-y-2.5 text-broca-slate">
                        <li><a href="{{ route('legal.show', 'terms') }}" class="hover:text-coral transition-colors">{{ __('app.legal_terms') }}</a></li>
                        <li><a href="{{ route('legal.show', 'privacy') }}" class="hover:text-coral transition-colors">{{ __('app.legal_privacy') }}</a></li>
                        <li><a href="{{ route('legal.show', 'medical-disclaimer') }}" class="hover:text-coral transition-colors">{{ __('app.legal_medical') }}</a></li>
                        <li><a href="{{ route('legal.show', 'contact') }}" class="hover:text-coral transition-colors">{{ __('app.legal_contact') }}</a></li>
                        <li><a href="{{ route('health') }}" target="_blank" class="hover:text-coral transition-colors font-mono">وضعیت سرویس (Health)</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Sub-Footer -->
            <div class="mt-12 pt-8 border-t border-broca-sand flex flex-wrap items-center justify-between gap-4 text-xs text-broca-slate">
                <p>
                    © {{ date('Y') }} آکادمی تخصصی علوم پزشکی بروکا (Broca). تمامی حقوق محفوظ است.
                </p>
                <div class="flex items-center gap-6">
                    <span>طراحی شده با استانداردهای آموزش نوین پزشکی</span>
                    <a href="#top" class="text-coral font-bold hover:underline">بازگشت به بالا ↑</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
