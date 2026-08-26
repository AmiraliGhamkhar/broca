@extends('layouts.app')

@section('title', 'پنل ادمین — ' . __('app.name'))

@section('content')
<section class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-black">پنل ادمین</h1>
    <p class="mt-2 text-broca-slate">مدیریت محتوای بروکا</p>

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-broca-sand rounded-2xl p-4">
            <h2 class="text-lg font-bold">کاربران</h2>
            <p class="mt-2 text-3xl font-black">{{ number_format($users) }}</p>
            <p class="mt-1 text-broca-slate">کاربر ثبت‌شده</p>
        </div>
        <div class="bg-white border border-broca-sand rounded-2xl p-4">
            <h2 class="text-lg font-bold">دوره‌ها</h2>
            <p class="mt-2 text-3xl font-black">{{ number_format($courses) }}</p>
            <p class="mt-1 text-broca-slate">دوره در {{ number_format($subjects) }} درس‌نامه</p>
        </div>
        <div class="bg-white border border-broca-sand rounded-2xl p-4">
            <h2 class="text-lg font-bold">اشتراک‌ها</h2>
            <p class="mt-2 text-3xl font-black">{{ number_format($activeSubscriptions) }}</p>
            <p class="mt-1 text-broca-slate">اشتراک فعال</p>
        </div>
        <div class="bg-white border border-broca-sand rounded-2xl p-4">
            <h2 class="text-lg font-bold">فاکتورها</h2>
            <p class="mt-2 text-3xl font-black">{{ number_format($paidInvoices) }}</p>
            <p class="mt-1 text-broca-slate">فاکتور پرداخت‌شده</p>
        </div>
        <div class="bg-white border border-broca-sand rounded-2xl p-4">
            <h2 class="text-lg font-bold">درآمد</h2>
            <p class="mt-2 text-3xl font-black">{{ number_format($revenueIrr / 10) }}</p>
            <p class="mt-1 text-broca-slate">تومان (جمع فاکتورهای پرداخت‌شده)</p>
        </div>
        <div class="bg-white border border-broca-sand rounded-2xl p-4">
            <h2 class="text-lg font-bold">ویدیوها</h2>
            <p class="mt-2 text-3xl font-black">{{ number_format($videos) }}</p>
            <p class="mt-1 text-broca-slate">ویدیوی ثبت‌شده</p>
        </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('admin.videos.index') }}" class="rounded-full bg-broca-accent px-5 py-2.5 font-bold text-white">مدیریت ویدیوها</a>
    </div>
</section>
@endsection
