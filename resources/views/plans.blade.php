@extends('layouts.app')

@section('title', 'اشتراک‌ها — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="text-sm font-black text-coral">دسترسی به یادگیری</p>
    <h1 class="mt-4 text-5xl font-black sm:text-7xl">اشتراک‌ها</h1>
    <p class="mt-5 max-w-2xl leading-8 text-ink/65">دو ویدیوی اول، یک جزوه، ده کارت مرور و یک نمونه‌سؤال رایگان است؛ برای دسترسی کامل به همهٔ محتوای دوره‌ها یکی از پلن‌های زیر را فعال کن.</p>

    <div class="mt-16 grid gap-5 md:grid-cols-3">
        @forelse ($plans as $plan)
            <article class="flex flex-col rounded-[2rem] border-2 border-ink p-6">
                <p class="text-sm font-black text-coral">{{ $plan->name }}</p>
                <h2 class="mt-8 text-3xl font-black">{{ $plan->duration_months ? $plan->duration_months.' ماهه' : 'رایگان' }}</h2>
                <p class="mt-5 leading-7 text-ink/65">{{ $plan->description ?: '[PLACEHOLDER: توضیح پلن]' }}</p>

                <p class="mt-10 text-2xl font-black">
                    @if ((int) $plan->price_irr > 0)
                        {{ number_format((int) $plan->price_irr / 10) }} تومان
                        <span class="block text-sm font-bold text-ink/50">{{ number_format((int) $plan->price_irr) }} ریال</span>
                    @else
                        رایگان
                    @endif
                </p>

                <div class="mt-8">
                    @auth
                        @if (config('broca.checkout_enabled') && (int) $plan->price_irr > 0 && (int) $plan->duration_months >= 1)
                            <form method="post" action="{{ route('checkout', $plan) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">خرید و پرداخت</button>
                            </form>
                            <p class="mt-3 text-xs text-ink/50">پرداخت امن از طریق درگاه زرین‌پال؛ پیش از انتقال، فاکتور صادر می‌شود.</p>
                        @elseif (! config('broca.checkout_enabled'))
                            <p class="rounded-xl border border-coral/40 bg-coral/10 px-4 py-3 text-sm font-bold text-coral">پرداخت تا آماده‌شدن نهایی سامانه موقتاً غیرفعال است.</p>
                        @else
                            <a href="{{ route('dashboard') }}" class="block rounded-full bg-ink px-7 py-4 text-center font-black text-cream">فعال است</a>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="block rounded-full bg-ink px-7 py-4 text-center font-black text-cream">ثبت‌نام کن</a>
                    @endauth
                </div>
            </article>
        @empty
            <div class="rounded-[2rem] border-2 border-ink p-8"><h2 class="text-2xl font-black">پلن‌ها پس از تأیید نهایی نمایش داده می‌شوند.</h2></div>
        @endforelse
    </div>

    <p class="mt-10 text-sm text-ink/50">[PLACEHOLDER: قیمت نهایی پس از تصمیم تجاری در جدول پلن‌ها ثبت می‌شود و این صفحه خودکار به‌روز می‌شود.]</p>
</section>
@endsection
