@extends('layouts.app')

@section('title', 'دسترسی مجاز نیست — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-black leading-none text-broca-accent sm:text-[10rem]" aria-hidden="true">۴۰۳</p>
    <h1 class="mt-2 text-3xl font-black sm:text-5xl">دسترسی به این بخش برای شما مجاز نیست</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-ink/65">
        این محتوا مخصوص اعضای دوره یا مشترکان است.
        اگر در دوره ثبت‌نام کرده‌اید، وارد حساب خود شوید؛ برای محتوای ویژه، اشتراک را فعال کنید.
    </p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        @guest
            <a href="{{ route('login') }}" class="rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">ورود به حساب</a>
        @else
            <a href="{{ route('plans') }}" class="rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">دیدن پلان‌های اشتراک</a>
        @endguest
        <a href="{{ route('catalog') }}" class="rounded-full bg-sun px-7 py-4 font-black focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">محتوای رایگان کاتالوگ</a>
    </div>
</section>
@endsection
