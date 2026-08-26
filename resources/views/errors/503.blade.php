@extends('layouts.app')

@section('title', 'به‌روزرسانی — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-black leading-none text-broca-accent sm:text-[10rem]" aria-hidden="true">۵۰۳</p>
    <h1 class="mt-2 text-3xl font-black sm:text-5xl">سایت در حال به‌روزرسانی است</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-ink/65">
        چند دقیقه‌ای از دسترس خارجیم تا چیزی بهتر آماده کنیم. به‌زودی برمی‌گردیم.
    </p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">تلاش دوباره</a>
    </div>
</section>
@endsection
