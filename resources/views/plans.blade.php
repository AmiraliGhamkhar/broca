@extends('layouts.app')

@section('title', 'پلن‌های اشتراک و دسترسی ویژه — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="text-center max-w-2xl mx-auto space-y-3">
        <span class="text-xs font-black text-coral">طرح‌های دسترسی و یادگیری بدون مرز</span>
        <h1 class="text-3xl sm:text-5xl font-black text-ink">پلن‌های اشتراک بروکا</h1>
        <p class="text-xs sm:text-sm text-broca-slate leading-7">
            در تمام دوره‌ها ۲ ویدیوی اول، ۱ جزوه خلاصه، ۱۰ فلش‌کارت و ۱ نمونه‌سؤال کاملاً رایگان است. برای دسترسی نامحدود به تمام آرشیو، یکی از اشتراک‌های زیر را انتخاب کنید.
        </p>
    </div>

    <div class="mt-14 grid gap-8 md:grid-cols-3 items-stretch">
        @forelse ($plans as $plan)
            @php $isPaid = (int) $plan->price_irr > 0 && (int) $plan->duration_months >= 1; @endphp
            <article class="interactive-card surface-panel p-8 rounded-3xl flex flex-col justify-between transition-all {{ $isPaid && $plan->duration_months == 3 ? 'border-2 border-coral shadow-lg relative bg-white' : 'bg-white/70' }}">
                @if ($isPaid && $plan->duration_months == 3)
                    <span class="absolute -top-3.5 right-6 rounded-full bg-coral px-3.5 py-1 text-[10px] font-black text-cream shadow-sm">
                        پیشنهاد ویژه دانشجویان ★
                    </span>
                @endif

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-coral">{{ $plan->name }}</span>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $isPaid ? 'bg-teal/15 text-teal' : 'bg-sun text-ink' }}">
                            {{ $plan->duration_months ? $plan->duration_months . ' ماهه' : 'رایگان دائمی' }}
                        </span>
                    </div>

                    <h2 class="text-2xl font-black text-ink">
                        @if ($isPaid)
                            {{ number_format((int) $plan->price_irr / 10) }} <span class="text-sm font-normal text-broca-slate">تومان</span>
                            <span class="block text-[11px] font-mono text-broca-slate font-normal mt-1" dir="ltr">{{ number_format((int) $plan->price_irr) }} ریال</span>
                        @else
                            رایگان <span class="text-sm font-normal text-broca-slate">(بدون هزینه)</span>
                        @endif
                    </h2>

                    <p class="text-xs text-broca-slate leading-6">
                        {{ $plan->description }}
                    </p>

                    <div class="pt-4 border-t border-broca-sand space-y-2 text-xs text-ink/80 font-bold">
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'دسترسی نامحدود به تمامی ویدیوها' : 'دسترسی به ۲ ویدیوی اول هر دوره' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'دانلود تمامی جزوات و اطلس‌های PDF' : 'دانلود ۱ جزوه نمونه در هر دوره' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'مرور فاصله‌دار نامحدود کارت‌های SM-2' : '۱۰ کارت مرور فاصله‌دار در هر دسته' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-teal">✓</span>
                            <span>{{ $isPaid ? 'شرکت در تمامی آزمون‌های تشخیصی' : '۱ نمونه سؤال تشخیصی در هر آزمون' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-broca-sand">
                    @auth
                        @if (config('broca.checkout_enabled') && $isPaid)
                            <form method="post" action="{{ route('checkout', $plan) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-full bg-ink py-3.5 font-black text-cream text-xs hover:bg-coral transition-all shadow-md">
                                    خرید اشتراک و اتصال به درگاه زرین‌پال
                                </button>
                            </form>
                            <p class="mt-2 text-[10px] text-center text-broca-slate">تأیید فوری و صدور فاکتور رسمی</p>
                        @elseif (! config('broca.checkout_enabled') && $isPaid)
                            <p class="rounded-xl border border-coral/30 bg-coral/10 p-3 text-center text-xs font-bold text-coral">
                                درگاه پرداخت در حال آماده‌سازی نهایی است.
                            </p>
                        @else
                            <a href="{{ route('dashboard') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-black text-cream text-xs hover:bg-coral transition-all">
                                ورود به داشبورد و استفاده رایگان
                            </a>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-black text-cream text-xs hover:bg-coral transition-all shadow-md">
                            ثبت‌نام و فعال‌سازی پلن
                        </a>
                    @endauth
                </div>
            </article>
        @empty
            <div class="col-span-3 surface-panel p-10 text-center">
                <p class="text-sm font-bold text-broca-slate">پلن‌های اشتراک پس از تأیید نهایی نمایش داده می‌شوند.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
