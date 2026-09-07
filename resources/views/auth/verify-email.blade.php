@extends('layouts.app')

@section('title', 'تأیید حساب کاربری — ' . __('app.name'))
@section('meta_description', 'تأیید حساب بروکا از طریق لینک ایمیل یا کد پیامکی ارسال‌شده به شمارهٔ همراه.')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $user = auth()->user();
    $phone = $user?->phone;
    // 0912*****89: recognizable to its owner, useless in a screenshot.
    $maskedPhone = $phone && mb_strlen($phone) >= 5
        ? mb_substr($phone, 0, 4) . str_repeat('*', max(1, mb_strlen($phone) - 6)) . mb_substr($phone, -2)
        : null;
    $phoneVerified = (bool) $user?->hasVerifiedPhone();
    $smsEnabled = (bool) config('broca.phone_verification.enabled', true);
@endphp

<section class="section-shell section-stack">
    <div class="mx-auto max-w-3xl space-y-3 text-center">
        <span class="icon-frame icon-frame-xl icon-frame-round mx-auto">
            <x-ui.icon name="shield" class="size-6" />
        </span>

        <h1 class="section-title mt-3">{{ $phoneVerified ? 'حساب شما تأیید شد' : 'حساب خود را تأیید کنید' }}</h1>
        <p class="mx-auto max-w-xl text-sm leading-7 text-muted">
            @if ($phoneVerified)
                شمارهٔ همراه شما تأیید شده است. می‌توانید ادامه دهید؛ برای دسترسی به اعلان‌ها و بازیابی گذرواژه، تأیید ایمیل را هم تکمیل کنید.
            @else
                برای فعال‌سازی کامل، یکی از دو راه زیر را انجام دهید: لینک ارسال‌شده به ایمیل، یا کد ارسال‌شده با پیامک به شمارهٔ همراه.
            @endif
        </p>
    </div>

    <div class="mx-auto mt-10 grid max-w-4xl gap-6 md:grid-cols-2">
        {{-- Channel 1: the emailed link --}}
        <div class="form-panel p-7">
            <div class="flex items-center gap-3 border-b border-hairline-soft pb-5">
                <span class="icon-frame-soft"><x-ui.icon name="document" class="size-5" /></span>
                <div>
                    <h2 class="text-base font-extrabold text-ink">تأیید با ایمیل</h2>
                    <p class="mt-1 text-[11px] text-muted">لینک تأیید به این نشانی ارسال شده است.</p>
                </div>
            </div>

            <p class="mt-5 text-sm font-bold text-ink" dir="ltr">{{ $user?->email }}</p>
            <p class="mt-3 text-xs leading-7 text-muted">
                صندوق ورودی و پوشهٔ Spam را بررسی کنید. اگر ایمیلی نرسیده، با دکمهٔ زیر دوباره درخواست دهید.
            </p>

            @if ($user?->hasVerifiedEmail())
                <p class="mt-5 rounded-2xl border border-teal/30 bg-teal/5 p-4 text-xs font-bold text-teal">
                    این ایمیل پیش‌تر تأیید شده است.
                </p>
            @endif

            <form method="post" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button type="submit" class="button-soft w-full justify-center">
                    <x-ui.icon name="refresh" class="size-4" />
                    ارسال دوبارهٔ لینک ایمیل
                </button>
            </form>
        </div>

        {{-- Channel 2: the SMS one-time code --}}
        <div class="form-panel p-7">
            <div class="flex items-center gap-3 border-b border-hairline-soft pb-5">
                <span class="icon-frame-soft"><x-ui.icon name="wallet" class="size-5" /></span>
                <div>
                    <h2 class="text-base font-extrabold text-ink">تأیید با پیامک</h2>
                    <p class="mt-1 text-[11px] text-muted">کد {{ \App\Support\PersianNumber::digits((int) config('broca.phone_verification.code_length', 6)) }} رقمی ارسال‌شده به موبایل را وارد کنید.</p>
                </div>
            </div>

            @if (! $smsEnabled)
                <p class="mt-5 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch">
                    تأیید با پیامک در این سامانه غیرفعال است؛ از لینک ایمیل استفاده کنید.
                </p>
            @elseif ($phoneVerified)
                <p class="mt-5 rounded-2xl border border-teal/30 bg-teal/5 p-4 text-xs font-bold text-teal">
                    شمارهٔ همراه شما تأیید شده است.
                </p>
            @elseif (! $maskedPhone)
                <p class="mt-5 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch">
                    برای این حساب شمارهٔ همراهی ثبت نشده است. از لینک ایمیل استفاده کنید.
                </p>
            @else
                <form method="post" action="{{ route('verification.phone.verify') }}" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label for="code" class="mb-1.5 block text-xs font-bold text-ink">کد تأیید پیامکی</label>
                        <input type="text" id="code" name="code" required autofocus autocomplete="one-time-code"
                               inputmode="numeric" maxlength="12" dir="ltr" spellcheck="false"
                               placeholder="••••••"
                               class="w-full rounded-xl border border-ink/20 bg-white p-3.5 text-center text-lg font-bold tracking-[0.4em] transition-[border-color,box-shadow] duration-200 focus:border-ink focus:shadow-float">
                        <x-forms.error field="code" />
                        <p class="mt-1.5 text-[11px] leading-6 text-muted">کد به شمارهٔ <bdi dir="ltr" class="font-bold text-ink">{{ $maskedPhone }}</bdi> ارسال شده و تا {{ \App\Support\PersianNumber::digits((int) config('broca.phone_verification.ttl_minutes', 10)) }} دقیقه معتبر است. ارقام فارسی یا لاتین هردو پذیرفته می‌شود.</p>
                    </div>

                    <button type="submit" class="button-primary w-full justify-center">
                        <x-ui.icon name="badge-check" class="size-4" />
                        تأیید شمارهٔ همراه
                    </button>
                </form>

                <form method="post" action="{{ route('verification.phone.send') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="button-soft w-full justify-center">
                        <x-ui.icon name="refresh" class="size-4" />
                        ارسال دوبارهٔ کد پیامکی
                    </button>
                    <x-forms.error field="phone_code" />
                </form>
            @endif
        </div>
    </div>

    @if (! $phoneVerified && $smsEnabled)
        <div class="mx-auto mt-8 max-w-4xl rounded-2xl border border-hairline-soft bg-surface-soft p-5 text-xs leading-7 text-muted">
            <strong class="text-ink">هر کدام از این دو راه برای فعال‌سازی کافی است.</strong>
            اگر ایمیل به دست شما نرسید (پوشهٔ هرزنامه، فیلتر سازمانی یا تأخیر سرور ایمیل)، نیازی به انتظار نیست؛ با کد پیامکی وارد شوید. تأیید فقط برای خرید اشتراک و دسترسی کامل الزامی است.
        </div>
    @endif

    <div class="pt-4 text-center text-xs text-muted">
        <form method="post" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="font-bold text-rausch underline">خروج از حساب</button>
            <span class="mx-2 text-hairline">|</span>
            <a href="{{ route('plans') }}" class="font-bold text-rausch underline">مقایسهٔ پلن‌های اشتراک</a>
        </form>
    </div>
</section>
@endsection
