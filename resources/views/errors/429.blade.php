@extends('layouts.app')

@section('title', 'درخواست بیش از حد — ' . __('app.name'))

@section('robots', 'noindex, nofollow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-24 text-center sm:px-8 lg:py-32">
    <p class="text-[7rem] font-bold leading-none text-broca-accent sm:text-[10rem]" aria-hidden="true">۴۲۹</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-5xl">درخواست‌های شما بیش از حد مجاز بوده است</h1>
    <p class="mx-auto mt-5 max-w-xl leading-9 text-ink/65">
        برای حفظ کیفیت سرویس، تعداد درخواست‌ها محدود است. کمی صبر کنید و دوباره تلاش کنید.
    </p>

    <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-full bg-ink px-7 py-4 font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">بازگشت به صفحهٔ اصلی</a>
    </div>
</section>
@endsection
