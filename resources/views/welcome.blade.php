@extends('layouts.app')

@section('title', __('app.name') . ' — ' . __('app.tagline'))
@section('meta_description', 'بروکا، پلتفرم فارسی آموزش پزشکی با ویدیوهای ساخت‌یافته، جزوات PDF، فلش‌کارت مرور فاصله‌دار، آزمون و شفافیت نویسنده و بازبین علمی.')
@section('canonical', route('home'))

@push('head')
    {{-- The hero photograph is the LCP element on mobile; preloading it
         saves the CSS → image discovery round trip. The WebP candidate is
         only advertised while a current twin exists (BrandAssets removes a
         stale one on upload), and both URLs follow operator uploads via
         /set_hero. --}}
    @php($heroPreloadJpg = \App\Support\BrandAssets::heroJpgUrl())
    @php($heroPreloadWebp = \App\Support\BrandAssets::heroWebpUrl())
    <link rel="preload" as="image" href="{{ $heroPreloadJpg }}" @if ($heroPreloadWebp !== null) imagesrcset="{{ $heroPreloadWebp }} 1x" @endif imagesizes="100vw" fetchpriority="high">
@endpush

@section('content')
@php
    $trustSignals = [
        ['title' => 'بازبینی علمی برای محتوای آموزشی', 'copy' => 'هر محتوای پزشکی پیش از انتشار می‌تواند مسیر نویسنده و بازبین علمی را طی کند.'],
        ['title' => 'شروع رایگان برای ارزیابی کیفیت', 'copy' => 'پیش از خرید می‌توان با نمونه‌درس، جزوه و فلش‌کارت منتخب، کیفیت آموزش را در عمل ارزیابی کرد.'],
        ['title' => 'یادگیری چندرسانه‌ای در یک محیط', 'copy' => 'ویدیو، PDF، فلش‌کارت و آزمون به‌جای پراکندگی، در یک تجربه منسجم جمع شده‌اند.'],
        ['title' => 'تعرفه‌های شفاف و پیگیری‌پذیر', 'copy' => 'تعرفه‌ها، وضعیت اشتراک و مسیر دسترسی به‌صورت واضح و قابل پیگیری نمایش داده می‌شود.'],
    ];

    // DB-driven (Round-6 audit F-2): the grid lists real, visible subjects —
    // hard-coded slugs here were the exact anti-pattern the footer fix
    // removed (a renamed subject silently produced empty catalog pages).
    // Editorial copy stays keyed by slug; a subject without a blurb falls
    // back to its own name/description, so the grid can never 404.
    $specialtyCopy = [
        'cardiovascular-physiology' => ['icon' => 'play', 'title' => 'فیزیولوژی قلب و همودینامیک', 'copy' => 'از پتانسیل عمل و گره‌های هدایتی تا همودینامیک عروق و تحلیل نوار قلب.', 'meta' => 'درس‌های ساخت‌یافته + جزوه PDF'],
        'neuroanatomy' => ['icon' => 'book', 'title' => 'نوروآناتومی و ناحیه بروکا', 'copy' => 'ساختار قشر مخ، مسیرهای عصبی، کالبدشناسی زبانی و ارتباط با سناریوهای بالینی.', 'meta' => 'اطلس آموزشی + مرور مفهومی'],
        'clinical-anatomy' => ['icon' => 'stack', 'title' => 'قفسه سینه و ساختارهای کلیدی', 'copy' => 'مرور استخوان‌بندی، عضلات، اعصاب و لندمارک‌های مهم با تمرکز بر فهم بالینی.', 'meta' => 'ویدیو + راهنمای مطالعه'],
        'cellular-physiology' => ['icon' => 'quiz', 'title' => 'غشا، انتقال و سیناپس', 'copy' => 'مبانی انتقال یونی، تنظیم پتانسیل غشا و ارتباط آن با عملکرد عصبی و عضلانی.', 'meta' => 'مرور پایه برای آزمون‌ها'],
    ];

    $specialties = \Illuminate\Support\Facades\Cache::remember('landing_specialties', 300, function () use ($specialtyCopy) {
        return \App\Models\Subject::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(4)
            ->get()
            ->map(fn ($subject) => [
                'label' => $subject->name,
                'title' => $specialtyCopy[$subject->slug]['title'] ?? $subject->name,
                'copy' => $specialtyCopy[$subject->slug]['copy'] ?? ($subject->description ?: 'مجموعهٔ درس، مرور و ارزیابی این شاخه در یک چارچوب آموزشی یکپارچه.'),
                'meta' => $specialtyCopy[$subject->slug]['meta'] ?? 'درس‌های ساخت‌یافته + مرور فاصله‌دار',
                'link' => route('subjects.show', $subject),
                'icon' => $specialtyCopy[$subject->slug]['icon'] ?? 'stack',
            ])
            ->all();
    });

    $pillars = [
        ['title' => 'ویدیوهای آموزشی کوتاه و هدفمند', 'copy' => 'هر درس با ساختار مشخص و قابل پیگیری ارائه می‌شود تا فراگیر دقیقاً بداند از کجا شروع کند و به کجا برسد.', 'icon' => 'play'],
        ['title' => 'جزوات قابل دانلود برای جمع‌بندی', 'copy' => 'خلاصه‌های PDF برای مرور شب امتحان، مطالعه آفلاین و رجوع سریع به نکات کلیدی طراحی شده‌اند.', 'icon' => 'document'],
        ['title' => 'مرور فاصله‌دار برای تثبیت', 'copy' => 'کارت‌ها بر اساس کیفیت یادآوری زمان‌بندی می‌شوند تا دانسته‌ها ماندگار شوند، نه موقت.', 'icon' => 'refresh'],
        ['title' => 'آزمون برای سنجش واقعی یادگیری', 'copy' => 'ارزیابی فقط یک نمره نیست؛ بخشی از حلقه یادگیری است تا ضعف‌ها زودتر مشخص شوند.', 'icon' => 'chart'],
    ];

    // $faculty is injected by HomeController from the `contributors` table
    // (real, visible, attached-to-published-content records only). It was
    // previously a hardcoded array of four invented doctors with invented
    // credentials — removed as a medical-trust violation.

    $faqs = [
        ['q' => 'آیا قبل از خرید می‌توان کیفیت محتوا را ارزیابی کرد؟', 'a' => 'بله. مدل دسترسی بروکا برای شروع رایگان طراحی شده تا فراگیر پیش از خرید، سبک تدریس، ساختار جزوات و کیفیت تجربه آموزشی را ببیند.'],
        ['q' => 'چرا نمایش نویسنده و بازبین علمی مهم است؟', 'a' => 'برای محتوای پزشکی، شفافیت در تولید و بازبینی یکی از مهم‌ترین عوامل اعتماد است. کاربر باید بداند محتوا چگونه و با چه سطحی از مسئولیت علمی منتشر شده است.'],
        ['q' => 'آیا مسیر یادگیری فقط ویدیومحور است؟', 'a' => 'خیر. طراحی بروکا بر پایه ترکیب ویدیو، جزوه، فلش‌کارت و آزمون است تا یادگیری صرفاً مصرف محتوا نباشد و به تثبیت دانسته‌ها برسد.'],
        ['q' => 'برای خرید اشتراک چه حس اطمینانی به کاربر داده می‌شود؟', 'a' => 'تعرفه شفاف، وضعیت دسترسی مشخص، پرداخت امن از طریق زرین‌پال و نمایش واضح وضعیت اشتراک، پایه اعتماد در تجربه خرید هستند.'],
    ];
@endphp

<x-landing-hero />

<section class="section-shell section-stack">
    <div class="trust-strip">
        @foreach ($trustSignals as $item)
            <div class="trust-card">
                <strong>{{ $item['title'] }}</strong>
                <span>{{ $item['copy'] }}</span>
            </div>
        @endforeach
    </div>
</section>

@if (! empty($specialties))
<section class="section-shell section-stack">
    <div class="section-intro">
        <span class="sr-only">حوزه‌های کلیدی آموزش</span>
        <h2 class="section-title mt-4">کتابخانه‌ای منسجم برای علوم پایه و آمادگی بالینی</h2>
        <p class="section-copy">به‌جای ارائه فهرستی پراکنده از محتوا، بروکا هر حوزه را با درس، مرور، جزوه و ارزیابی در یک چارچوب آموزشی یکپارچه ارائه می‌کند.</p>
    </div>

    <div class="subtle-grid cols-4 mt-10">
        @foreach ($specialties as $item)
            <article class="feature-card h-full flex flex-col justify-between gap-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="icon-frame">
                            <x-ui.icon :name="$item['icon']" class="size-5" />
                        </span>
                        <span class="badge-neutral">{{ $item['label'] }}</span>
                    </div>

                    <div>
                        <h3 class="text-lg font-extrabold text-ink leading-7">{{ $item['title'] }}</h3>
                        <p class="mt-3">{{ $item['copy'] }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-hairline-soft flex items-center justify-between gap-4">
                    <span class="text-xs font-bold text-muted">{{ $item['meta'] }}</span>
                    <a href="{{ $item['link'] }}" class="text-xs font-bold text-rausch-text hover:underline">ورود به مجموعه ←</a>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-5 section-intro">
            <span class="sr-only">متد یادگیری بروکا</span>
            <h2 class="section-title mt-4">طراحی برای فهم، مرور و سنجش؛ نه فقط تماشای محتوا</h2>
            <p class="section-copy">یک تجربه آموزشی حرفه‌ای زمانی شکل می‌گیرد که محتوای اصلی، مرور فعال و ارزیابی در یک مسیر مشخص کنار هم قرار بگیرند.</p>
        </div>

        <div class="lg:col-span-7 subtle-grid cols-2">
            @foreach ($pillars as $item)
                <article class="editorial-card h-full flex flex-col gap-4">
                    <div class="flex items-start gap-4">
                        <span class="icon-frame-soft icon-frame-round">
                            <x-ui.icon :name="$item['icon']" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-ink">{{ $item['title'] }}</h3>
                            <p class="mt-3 text-muted leading-7">{{ $item['copy'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-4 section-intro">
            <span class="sr-only">اعتماد علمی</span>
            <h2 class="section-title mt-4">ترکیب نویسنده، بازبین و مسیر انتشار مسئولانه</h2>
            <p class="section-copy">در آموزش پزشکی، اعتماد صرفاً از ظاهر خوب به‌دست نمی‌آید. مخاطب باید بداند محتوا چه کسی نوشته، چه کسی بازبینی کرده و با چه معیارهایی منتشر شده است.</p>
        </div>

        <div class="lg:col-span-8 subtle-grid cols-2">
            @forelse ($faculty as $person)
                <article class="editorial-card">
                    <div class="flex items-start gap-4">
                        <span class="icon-frame-soft icon-frame-lg icon-frame-round">
                            <x-ui.icon name="users" class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-ink">{{ $person->name }}</h3>
                            <p class="mt-1 text-xs font-bold text-rausch-text">{{ $person->credentials }}</p>
                            @if ($person->specialty)
                                <p class="mt-1 text-xs text-muted-soft">{{ $person->specialty }}</p>
                            @endif
                            @if ($person->bio)
                                <p class="mt-3 text-sm leading-7 text-muted">{{ $person->bio }}</p>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                {{-- Honest empty state: describe the review PROCESS, never
                     invent the people. Renders until real contributors are
                     attached to published content. --}}
                <article class="editorial-card is-soft sm:col-span-2">
                    <h3 class="text-base font-extrabold text-ink">مسیر انتشار محتوا در بروکا</h3>
                    <p class="mt-3 text-sm leading-7 text-muted">هر درس پیش از انتشار، مسیر نویسنده و بازبین علمی را طی می‌کند و نام و اعتبارنامهٔ هر دو، روی همان درس نمایش داده می‌شود. فهرست کامل نویسندگان و بازبینان به‌محض انتشار نخستین دوره‌ها در همین بخش منتشر می‌شود.</p>
                </article>
            @endforelse
        </div>
    </div>
</section>

<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-7 editorial-card">
            <span class="sr-only">جریان شروع حرفه‌ای</span>
            <h2 class="text-2xl font-black text-ink mt-4">از ساخت حساب رایگان تا تصمیم خرید، مسیر باید روشن باشد</h2>
            <p class="section-copy max-w-2xl">برای ایجاد اعتماد، ابتدا تجربه واقعی یادگیری نمایش داده می‌شود. سپس پلن‌ها، سطح دسترسی، جزئیات پرداخت و وضعیت اشتراک با زبان روشن و بدون دعوت‌به‌اقدام‌های مبهم توضیح داده می‌شوند.</p>
            <div class="flex flex-wrap gap-3 mt-6">
                <a href="{{ route('plans') }}" class="btn-cta-primary">
                    <x-ui.icon name="wallet" class="size-4" />
                    مشاهده پلن‌های اشتراک
                </a>
                <a href="{{ route('blog.index') }}" class="btn-cta-secondary">
                    <x-ui.icon name="document" class="size-4" />
                    مشاهده مقالات آموزشی
                </a>
            </div>
        </div>

        <div class="lg:col-span-5 editorial-card is-soft">
            <h3 class="text-lg font-extrabold text-ink">چرا این طراحی حرفه‌ای‌تر حس می‌شود؟</h3>
            <div class="trust-list mt-5">
                <div class="trust-list-item">
                    <span class="icon-frame"><x-ui.icon name="shield" class="size-5" /></span>
                    <div>
                        <strong>تمرکز بر اعتماد و مسئولیت</strong>
                        <span>اطلاعات مهم مثل وضعیت انتشار، بازبینی علمی و محدودیت‌های دسترسی پنهان نمی‌شوند.</span>
                    </div>
                </div>
                <div class="trust-list-item">
                    <span class="icon-frame"><x-ui.icon name="chart" class="size-5" /></span>
                    <div>
                        <strong>وضوح اطلاعاتی بالا</strong>
                        <span>سطوح محتوا، مسیر یادگیری، آمار و تفاوت پلن‌ها قابل اسکن و سریع‌فهم هستند.</span>
                    </div>
                </div>
                <div class="trust-list-item">
                    <span class="icon-frame"><x-ui.icon name="spark" class="size-5" /></span>
                    <div>
                        <strong>حس محصول بالغ</strong>
                        <span>به‌جای تزئینات زیاد، از نظم بصری، فاصله‌گذاری و مؤلفه‌های یکپارچه استفاده می‌شود.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-4 section-intro">
            <span class="sr-only">پرسش‌های متداول</span>
            <h2 class="section-title mt-4">چند سؤال مهم پیش از شروع</h2>
            <p class="section-copy">این بخش هم به تصمیم‌گیری آگاهانه کمک می‌کند و هم بخشی از تصویر حرفه‌ای و شفاف محصول است.</p>
        </div>

        <div class="lg:col-span-8 space-y-4">
            @foreach ($faqs as $item)
                <details class="faq-item">
                    <summary>{{ $item['q'] }}</summary>
                    <p>{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="section-shell section-stack section-stack-no-top">
    <div class="editorial-card is-dark text-center space-y-5">
        <h2 class="text-2xl sm:text-4xl font-black text-white max-w-3xl mx-auto leading-tight">همین حالا حساب رایگان بسازید و کیفیت تجربه یادگیری بروکا را از نزدیک ارزیابی کنید</h2>
        <p class="max-w-2xl mx-auto text-sm leading-8 text-white/70">نمونه‌درس‌ها، بخشی از جزوات، فلش‌کارت‌های منتخب و محتوای وبلاگ برای آشنایی اولیه در دسترس‌اند تا تصمیم خرید بر پایه تجربه واقعی گرفته شود.</p>
        <div class="flex flex-wrap justify-center gap-3 pt-2">
            <a href="{{ route('register') }}" class="btn-cta-primary">ساخت حساب رایگان</a>
            <a href="{{ route('catalog') }}" class="btn-cta-secondary inline-flex items-center justify-center gap-2 min-h-[3rem] rounded border border-white/20 bg-white text-ink text-sm font-bold px-5 hover:bg-surface-soft transition-colors">بررسی دوره‌ها</a>
        </div>
    </div>
</section>

@push('scripts')
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['a'],
            ],
        ])->all(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endpush
@endsection
