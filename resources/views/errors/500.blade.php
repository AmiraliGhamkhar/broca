@extends('layouts.app')

@section('title', 'خطای سرور — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-black leading-none text-broca-accent sm:text-[10rem]" aria-hidden="true">۵۰۰</p>
    <h1 class="mt-2 text-3xl font-black sm:text-5xl">خطایی از سمت سرور رخ داد</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-ink/65">
        این خطا از طرف ماست، نه شما. گزارش آن ثبت شده است؛ چند لحظه بعد دوباره تلاش کنید.
    </p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">بازگشت به صفحهٔ اصلی</a>
        <a href="{{ route('catalog') }}" class="rounded-full bg-sun px-7 py-4 font-black focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">مرور دوره‌ها</a>
    </div>
</section>
@endsection
