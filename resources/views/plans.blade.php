@extends('layouts.app')

@section('title', 'پلن‌های اشتراک و دسترسی ویژه — ' . __('app.name'))
@section('meta_description', 'مقایسه پلن‌های اشتراک بروکا با قیمت شفاف، مدت دسترسی، پرداخت امن و توضیح تفاوت حساب رایگان و اشتراک ویژه.')
@section('canonical', route('plans'))

@section('content')
@php
    // FAQ copy lives in App\Support\PlanFaq — the FAQPage JSON-LD block and
    // the /plans.md twin render from the same array.
    $faq = \App\Support\PlanFaq::all();
@endphp

<section class="section-shell section-stack">
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <span class="eyebrow">تعرفه‌های شفاف و قابل اتکا</span>
        <h1 class="section-title mt-2">پلن‌های اشتراک بروکا برای دسترسی حرفه‌ای به آموزش پزشکی</h1>
        <p class="section-copy max-w-2xl mx-auto">هدف این صفحه فقط فروش نیست؛ باید تفاوت حساب رایگان و اشتراک ویژه را شفاف نشان دهد، حس امنیت در پرداخت ایجاد کند و تصمیم خرید را ساده‌تر کند.</p>
    </div>

    <div class="trust-strip mt-10">
        <div class="trust-card">
            <strong>قیمت‌گذاری شفاف</strong>
            <span>مبلغ هر پلن و بازه دسترسی آن روشن و بدون ابهام نمایش داده می‌شود.</span>
        </div>
        <div class="trust-card">
            <strong>پرداخت امن زرین‌پال</strong>
            <span>فرآیند پرداخت با ثبت وضعیت فاکتور و پیگیری سمت سرور همراه است.</span>
        </div>
        <div class="trust-card">
            <strong>شروع رایگان پیش از خرید</strong>
            <span>کاربر می‌تواند قبل از پرداخت، تجربه واقعی کیفیت محتوا را بسنجد.</span>
        </div>
        <div class="trust-card">
            <strong>نمایش واضح وضعیت اشتراک</strong>
            <span>اگر اشتراک فعال داشته باشید، سیستم آن را به‌وضوح اعلام می‌کند.</span>
        </div>
    </div>

    <div class="mt-12 grid gap-8 md:grid-cols-3 items-stretch">
        @forelse ($plans as $plan)
            @php
                $isPaid = (int) $plan->price_irr > 0 && (int) $plan->duration_months >= 1;
                $isRecommended = $isPaid && (int) $plan->duration_months === 3;
            @endphp

            <article class="editorial-card relative flex flex-col justify-between {{ $isRecommended ? 'border-rausch shadow-float' : '' }}" data-reveal>
                @if ($isRecommended)
                    <span class="badge-soft absolute -top-3 right-6 shadow-float">پیشنهاد متعادل برای بیشتر دانشجویان</span>
                @endif

                <div class="space-y-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-extrabold text-ink">{{ $plan->name }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $plan->description }}</p>
                        </div>
                        <span class="{{ $isPaid ? 'badge-success' : 'badge-neutral' }} whitespace-nowrap">
                            {{ $plan->duration_months ? $plan->duration_months . ' ماه دسترسی' : 'دسترسی رایگان' }}
                        </span>
                    </div>

                    <div class="rounded-[1.5rem] bg-surface-soft p-4">
                        <h2 class="font-sans text-3xl font-black text-ink" dir="ltr">
                            @if ($isPaid)
                                {{ number_format((int) $plan->price_irr / 10) }}
                                <span class="text-sm font-bold text-muted">تومان</span>
                            @else
                                0 <span class="text-sm font-bold text-muted">تومان</span>
                            @endif
                        </h2>
                        <p class="mt-1 text-[11px] text-muted" dir="ltr">{{ number_format((int) $plan->price_irr) }} ریال</p>
                    </div>

                    <div class="space-y-3">
                        <div class="trust-list-item is-soft">
                            <span class="icon-frame-soft"><x-ui.icon name="play" class="size-5" /></span>
                            <div>
                                <strong>{{ $isPaid ? 'دسترسی گسترده به ویدیوهای آموزشی' : 'شروع با نمونه‌درس‌های منتخب' }}</strong>
                                <span>{{ $isPaid ? 'تمام درس‌های ویدیویی منتشرشده قابل استفاده هستند.' : 'تا ۲ ویدیوی منتخب، در کل آرشیو و همه دوره‌ها — سهمیهٔ رایگان به‌صورت جدا برای هر دوره محاسبه نمی‌شود، بلکه در کل آرشیو (همه دوره‌ها روی هم) به‌کار می‌رود تا سبک تدریس و ساختار محتوا را ارزیابی کنید.' }}</span>
                            </div>
                        </div>
                        <div class="trust-list-item is-soft">
                            <span class="icon-frame-soft"><x-ui.icon name="document" class="size-5" /></span>
                            <div>
                                <strong>{{ $isPaid ? 'دسترسی به جزوات و فایل‌های PDF' : 'امکان مشاهده نمونه جزوه' }}</strong>
                                <span>{{ $isPaid ? 'جزوات و فایل‌های آموزشی منتشرشده برای مطالعه و جمع‌بندی در دسترس‌اند.' : 'کاربر قبل از خرید می‌تواند با سبک جزوات و کیفیت آن‌ها آشنا شود.' }}</span>
                            </div>
                        </div>
                        <div class="trust-list-item is-soft">
                            <span class="icon-frame-soft"><x-ui.icon name="refresh" class="size-5" /></span>
                            <div>
                                <strong>{{ $isPaid ? 'مرور فاصله‌دار و سنجش یادگیری' : 'مرور و آزمون برای آشنایی اولیه' }}</strong>
                                <span>{{ $isPaid ? 'فلش‌کارت‌ها و آزمون‌ها بخشی از چرخه یادگیری روزانه شما می‌شوند.' : 'سطح رایگان برای شناخت مدل یادگیری و تصمیم‌گیری آگاهانه طراحی شده است.' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-5 border-t border-hairline-soft space-y-3">
                    @auth
                        @if ($hasActiveSubscription)
                            <p class="rounded-2xl border border-teal/30 bg-teal/5 p-3.5 text-center text-xs font-bold text-teal">
                                شما هم‌اکنون یک اشتراک فعال دارید؛ برای جلوگیری از تداخل دسترسی، خرید جدید تا پایان پلن فعلی غیرفعال است.
                            </p>
                            <a href="{{ route('dashboard') }}" class="button-secondary w-full">مشاهده وضعیت اشتراک</a>
                        @elseif (config('broca.checkout_enabled') && $isPaid)
                            <form method="post" action="{{ route('checkout', $plan) }}">
                                @csrf
                                <button type="submit" class="button-primary w-full">خرید اشتراک و ورود به درگاه امن</button>
                            </form>
                            <p class="text-[11px] text-center text-muted">پرداخت از طریق زرین‌پال انجام می‌شود و وضعیت فاکتور بعد از بازگشت از درگاه قابل پیگیری است.</p>
                        @elseif (! config('broca.checkout_enabled') && $isPaid)
                            <p class="rounded-2xl border border-rausch/30 bg-rausch-tint p-3.5 text-center text-xs font-bold text-rausch">
                                درگاه پرداخت فعلاً برای این پلن غیرفعال است. پس از فعال‌سازی نهایی می‌توانید خرید را تکمیل کنید.
                            </p>
                        @else
                            <a href="{{ route('dashboard') }}" class="button-soft w-full">ورود به داشبورد و استفاده از حساب رایگان</a>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="button-primary w-full">ثبت‌نام و انتخاب این پلن</a>
                    @endauth
                </div>
            </article>
        @empty
            <div class="col-span-3 surface-panel p-10 text-center">
                <p class="text-sm font-bold text-muted">پلن‌های اشتراک پس از تأیید نهایی در این صفحه نمایش داده می‌شوند.</p>
            </div>
        @endforelse
    </div>
</section>

<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-4 section-intro">
            <span class="eyebrow">حس امنیت در خرید</span>
            <h2 class="section-title mt-4">چرا این صفحه باید حرفه‌ای و اعتمادپذیر به نظر برسد؟</h2>
            <p class="section-copy">کاربر در مرحله خرید بیش از هر زمان دیگری به شفافیت، زبان دقیق و نشانه‌های اعتماد نیاز دارد؛ نه فقط رنگ و دکمه زیبا.</p>
        </div>

        <div class="lg:col-span-8 subtle-grid cols-2">
            <div class="feature-card">
                <span class="icon-frame-soft mb-4"><x-ui.icon name="wallet" class="size-5" /></span>
                <h3 class="text-base font-extrabold text-ink">تعرفه و مزایا با زبان ساده</h3>
                <p class="mt-3">کاربر دقیقاً می‌فهمد برای چه چیزی پرداخت می‌کند، چه مدتی دسترسی دارد و چه تفاوتی میان سطوح مختلف وجود دارد.</p>
            </div>
            <div class="feature-card">
                <span class="icon-frame-soft mb-4"><x-ui.icon name="shield" class="size-5" /></span>
                <h3 class="text-base font-extrabold text-ink">هماهنگی CTA با وضعیت واقعی سیستم</h3>
                <p class="mt-3">اگر اشتراک فعال باشد، اگر درگاه موقتاً غیرفعال باشد یا اگر کاربر مهمان باشد، رابط همان وضعیت واقعی را صادقانه نشان می‌دهد.</p>
            </div>
            <div class="feature-card">
                <span class="icon-frame-soft mb-4"><x-ui.icon name="badge-check" class="size-5" /></span>
                <h3 class="text-base font-extrabold text-ink">مسیر خرید قابل پیگیری</h3>
                <p class="mt-3">ثبت فاکتور و بازگشت شفاف نتیجه پرداخت بخشی از حس حرفه‌ای بودن و قابل اتکا بودن تجربه است.</p>
            </div>
            <div class="feature-card">
                <span class="icon-frame-soft mb-4"><x-ui.icon name="spark" class="size-5" /></span>
                <h3 class="text-base font-extrabold text-ink">بدون اغراق تبلیغاتی</h3>
                <p class="mt-3">لحن این صفحه باید متین، روشن و مبتنی بر ارزش واقعی محصول باشد؛ نه صرفاً فروش‌محور و هیجانی.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-shell section-stack section-stack-no-top">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($faq as $item)
            <details class="faq-item">
                <summary>{{ $item['q'] }}</summary>
                <p>{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>
</section>

@push('scripts')
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'پلن‌های اشتراک بروکا',
        'description' => 'مقایسه پلن‌های اشتراک بروکا با قیمت شفاف، مدت دسترسی و پرداخت امن.',
        'url' => route('plans'),
        'inLanguage' => 'fa-IR',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('app.name'), 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'پلن‌های اشتراک', 'item' => route('plans')],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'پلن‌های اشتراک بروکا',
        'numberOfItems' => $plans->count(),
        'itemListElement' => $plans->values()->map(function ($plan, $index) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => array_filter([
                    '@type' => 'Offer',
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => (string) $plan->price_irr,
                    'priceCurrency' => 'IRR',
                    'availability' => $plan->is_active ? 'https://schema.org/InStock' : 'https://schema.org/Discontinued',
                    'category' => 'Subscription',
                    'url' => route('plans'),
                ], fn ($value) => $value !== null && $value !== ''),
            ];
        })->all(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faq)->map(fn ($item) => [
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
