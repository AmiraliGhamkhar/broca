@extends('layouts.app')

@section('title', 'نشست منقضی شده — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-black leading-none text-broca-accent sm:text-[10rem]" aria-hidden="true">۴۱۹</p>
    <h1 class="mt-2 text-3xl font-black sm:text-5xl">نشست شما منقضی شده است</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-ink/65">
        برای امنیت حساب، بعد از مدتی بی‌فعالیت نشست بسته می‌شود. دوباره وارد شوید تا از همان‌جا ادامه دهید.
    </p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('login') }}" class="rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">ورود دوباره</a>
        <a href="{{ route('home') }}" class="rounded-full bg-sun px-7 py-4 font-black focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">صفحهٔ اصلی</a>
    </div>
</section>
@endsection
