{{-- Generic error fallback: Laravel renders this for any HTTP error status
     without a dedicated view (403, 419, 429, 500, 503, …). --}}
@extends('layouts.app')

@section('title', 'خطا — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@php
    $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
    $persianCode = strtr((string) $code, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    $messages = [
        403 => ['دسترسی به این بخش برای شما مجاز نیست', 'اگر فکر می‌کنید خطا رخ داده، وارد حساب خود شوید یا اشتراک را فعال کنید.'],
        419 => ['نشست شما منقضی شده است', 'برای ادامه، دوباره وارد شوید.'],
        429 => ['درخواست‌های شما بیش از حد مجاز بوده است', 'کمی صبر کنید و دوباره تلاش کنید.'],
        500 => ['خطایی از سمت سرور رخ داد', 'تیم ما در جریان است؛ چند لحظه بعد دوباره تلاش کنید.'],
        503 => ['سایت در حال به‌روزرسانی است', 'به‌زودی برمی‌گردیم.'],
    ];
    [$heading, $body] = $messages[$code] ?? ['خطایی رخ داد', 'درخواست شما کامل نشد؛ دوباره تلاش کنید.'];
@endphp

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-bold leading-none text-rausch sm:text-[10rem]" aria-hidden="true">{{ $persianCode }}</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-5xl">{{ $heading }}</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-muted">{{ $body }}</p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        @if (in_array($code, [419, 403], true))
            <a href="{{ route('login') }}" class="rounded-full bg-ink px-7 py-4 font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">ورود به حساب</a>
        @endif
        <a href="{{ route('home') }}" class="rounded-full {{ in_array($code, [419, 403], true) ? 'bg-surface-soft' : 'bg-ink text-white' }} px-7 py-4 font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">بازگشت به صفحهٔ اصلی</a>
    </div>
</section>
@endsection
