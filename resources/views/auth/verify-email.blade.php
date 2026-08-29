@extends('layouts.app')

@section('title', 'تأیید آدرس ایمیل — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-lg px-4 sm:px-6 py-20 sm:py-28 text-center">
    <div class="form-panel p-8 sm:p-12 space-y-6">
        <span class="grid size-16 place-items-center rounded-3xl bg-rausch-tint text-rausch text-2xl font-bold mx-auto shadow-md">
            ✉️
        </span>

        <div class="space-y-2">
            <h1 class="font-display text-2xl sm:text-3xl text-ink">ایمیل خود را تأیید کنید</h1>
            <p class="text-xs text-muted leading-6 max-w-sm mx-auto">
                لینک تأیید حساب به آدرس ایمیل شما ارسال شد. لطفاً صندوق ورودی (یا پوشه Spam) را بررسی و روی لینک فعال‌سازی بزنید.
            </p>
        </div>

        <form method="post" action="{{ route('verification.send') }}" class="pt-2">
            @csrf
            <button type="submit" class="rounded-full bg-rausch px-7 py-3 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
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
