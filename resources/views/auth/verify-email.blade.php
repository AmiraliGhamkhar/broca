@extends('layouts.app')

@section('title', 'تأیید آدرس ایمیل — ' . __('app.name'))
@section('meta_description', 'تأیید ایمیل برای فعال‌سازی کامل حساب بروکا و ادامه دسترسی به تجربه آموزشی.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="section-shell section-stack">
    <div class="max-w-3xl mx-auto editorial-card text-center space-y-6">
        <span class="icon-frame icon-frame-xl icon-frame-round mx-auto">
            <x-ui.icon name="document" class="size-6" />
        </span>

        <div class="space-y-3">
            <span class="eyebrow">تأیید هویت ایمیلی</span>
            <h1 class="section-title mt-3">ایمیل خود را تأیید کنید</h1>
            <p class="text-sm leading-7 text-muted max-w-xl mx-auto">لینک تأیید حساب به ایمیل شما ارسال شده است. برای فعال‌سازی کامل حساب، صندوق ورودی یا پوشه Spam را بررسی کنید و روی لینک تأیید بزنید.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 text-right">
            <div class="meta-card is-soft">
                <p class="text-xs font-bold text-ink">چرا این مرحله مهم است؟</p>
                <p class="mt-2 text-xs leading-6 text-muted">برای اطمینان از دسترسی شما به پیام‌های مهم حساب، اطلاعیه‌های امنیتی و بازیابی گذرواژه.</p>
            </div>
            <div class="meta-card is-soft">
                <p class="text-xs font-bold text-ink">اگر ایمیل را پیدا نکردید</p>
                <p class="mt-2 text-xs leading-6 text-muted">می‌توانید از دکمه زیر برای ارسال دوباره لینک استفاده کنید یا آدرس ایمیل واردشده را بررسی نمایید.</p>
            </div>
        </div>

        <form method="post" action="{{ route('verification.send') }}" class="pt-2">
            @csrf
            <button type="submit" class="button-primary">
                <x-ui.icon name="document" class="size-4" />
                ارسال دوباره لینک تأیید
            </button>
        </form>

        <div class="pt-4 border-t border-hairline-soft text-xs text-muted">
            <form method="post" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-rausch underline font-bold">خروج از حساب</button>
            </form>
        </div>
    </div>
</section>
@endsection
