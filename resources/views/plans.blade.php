@extends('layouts.app')

@section('title', 'پلن‌های اشتراک و دسترسی ویژه — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="text-xs font-bold text-rausch">طرح‌های دسترسی و یادگیری بدون مرز</span>
        <h1 class="font-display text-3xl sm:text-5xl text-ink">پلن‌های اشتراک بروکا</h1>
        <p class="text-xs sm:text-sm text-muted leading-7">
            حساب رایگان در کل آرشیو (همه دوره‌ها روی هم) به ۲ ویدیوی منتخب، ۱ جزوه، ۱۰ فلش‌کارت و ۱ نمونه‌سؤال دسترسی می‌دهد. برای دسترسی نامحدود به تمام آرشیو، یکی از اشتراک‌های زیر را انتخاب کنید.
        </p>
    </div>

    <div class="mt-14 grid gap-8 md:grid-cols-3 items-stretch">
        @forelse ($plans as $plan)
            @php $isPaid = (int) $plan->price_irr > 0 && (int) $plan->duration_months >= 1; @endphp
            <article class="interactive-card surface-panel p-8 rounded-3xl flex flex-col justify-between transition-all {{ $isPaid && $plan->duration_months == 3 ? 'border-2 border-rausch shadow-float relative bg-white' : 'bg-white' }}">
                @if ($isPaid && $plan->duration_months == 3)
                    <span class="absolute -top-3.5 right-6 rounded-full bg-rausch px-3.5 py-1 text-[10px] font-bold text-white shadow-float">
                        پیشنهاد ویژه دانشجویان ★
                    </span>
                @endif

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rausch">{{ $plan->name }}</span>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $isPaid ? 'bg-teal/10 text-teal' : 'bg-surface-soft text-ink' }}">
                            {{ $plan->duration_months ? $plan->duration_months . ' ماهه' : 'رایگان دائمی' }}
                        </span>
                    </div>

                    <h2 class="font-display text-2xl text-ink">
                        @if ($isPaid)
                            {{ number_format((int) $plan->price_irr / 10) }} <span class="text-sm font-normal text-muted">تومان</span>
                            <span class="block text-[11px] font-mono text-muted font-normal mt-1" dir="ltr">{{ number_format((int) $plan->price_irr) }} ریال</span>
                        @else
                            رایگان <span class="text-sm font-normal text-muted">(بدون هزینه)</span>
                        @endif
                    </h2>

                    <p class="text-xs text-muted leading-6">
                        {{ $plan->description }}
                    </p>

                    <div class="pt-4 border-t border-hairline-soft space-y-2 text-xs text-ink/80 font-bold">
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'دسترسی نامحدود به تمامی ویدیوها' : 'تا ۲ ویدیوی منتخب، در کل آرشیو و همه دوره‌ها' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'دانلود تمامی جزوات و اطلس‌های PDF' : 'تا ۱ جزوه PDF در کل آرشیو' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'مرور فاصله‌دار نامحدود کارت‌های SM-2' : 'تا ۱۰ فلش‌کارت مرور فاصله‌دار در کل آرشیو' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'شرکت در تمامی آزمون‌های تشخیصی' : 'تا ۱ نمونه‌سؤال تشخیصی در کل آرشیو' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-hairline-soft">
                    @auth
                        @if ($hasActiveSubscription)
                            <p class="rounded-xl border border-teal/30 bg-teal/5 p-3 text-center text-xs font-bold text-teal">
                                شما هم‌اکنون یک اشتراک فعال دارید؛ تا پایان آن، خرید جدید ممکن نیست.
                            </p>
                            <a href="{{ route('dashboard') }}" class="mt-3 block w-full rounded-full bg-ink py-3.5 text-center font-bold text-white text-xs hover:bg-rausch transition-colors">
                                مشاهده وضعیت اشتراک من
                            </a>
                        @elseif (config('broca.checkout_enabled') && $isPaid)
                            <form method="post" action="{{ route('checkout', $plan) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-full bg-rausch py-3.5 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                                    خرید اشتراک و اتصال به درگاه زرین‌پال
                                </button>
                            </form>
                            <p class="mt-2 text-[10px] text-center text-muted">تأیید فوری و صدور فاکتور رسمی</p>
                        @elseif (! config('broca.checkout_enabled') && $isPaid)
                            <p class="rounded-xl border border-rausch/30 bg-rausch-tint p-3 text-center text-xs font-bold text-rausch">
                                درگاه پرداخت در حال آماده‌سازی نهایی است.
                            </p>
                        @else
                            <a href="{{ route('dashboard') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-bold text-white text-xs hover:bg-rausch transition-colors">
                                ورود به داشبورد و استفاده رایگان
                            </a>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="block w-full rounded-full bg-rausch py-3.5 text-center font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                            ثبت‌نام و فعال‌سازی پلن
                        </a>
                    @endauth
                </div>
            </article>
        @empty
            <div class="col-span-3 surface-panel p-10 text-center">
                <p class="text-sm font-bold text-muted">پلن‌های اشتراک پس از تأیید نهایی نمایش داده می‌شوند.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
