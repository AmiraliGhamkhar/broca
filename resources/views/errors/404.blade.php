@extends('layouts.app')

@section('title', 'صفحه پیدا نشد — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-bold leading-none text-broca-accent sm:text-[10rem]" aria-hidden="true">۴۰۴</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-5xl">این صفحه اینجا نیست</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-ink/65">
        ممکن است آدرس تغییر کرده باشد، صفحه حذف شده باشد یا لینک اشتباه وارد شده باشد.
        نگران نباش — مسیر یادگیری از اینجا ادامه دارد.
    </p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded bg-ink px-7 py-4 font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">بازگشت به صفحهٔ اصلی</a>
        <a href="{{ route('catalog') }}" class="rounded bg-surface-soft px-7 py-4 font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">مرور دوره‌ها</a>
        @auth
            <a href="{{ route('dashboard') }}" class="rounded border border-ink/30 px-7 py-4 font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">داشبورد من</a>
        @else
            <a href="{{ route('login') }}" class="rounded border border-ink/30 px-7 py-4 font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">ورود به حساب</a>
        @endauth
    </div>

    <p class="mt-10 text-sm font-bold text-ink/50">اگر فکر می‌کنید این خطا از طرف ماست، با پشتیبانی تماس بگیرید.</p>
</section>
@endsection
