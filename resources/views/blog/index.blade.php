@extends('layouts.app')

@section('title', 'مقالات و وبلاگ آموزش پزشکی — ' . __('app.name'))

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="space-y-3 pb-8 border-b border-broca-sand max-w-2xl">
        <span class="text-xs font-black text-coral">دانشنامه و مقالات علمی</span>
        <h1 class="text-3xl sm:text-5xl font-black text-ink">وبلاگ و یافته‌های پزشکی</h1>
        <p class="text-xs sm:text-sm text-broca-slate leading-7">
            مجموعه یادداشت‌های بالینی، مرور گایدلاین‌های درمانی، متدهای نوین به خاطر سپاری و آموزش علوم اعصاب و فیزیولوژی.
        </p>
    </div>

    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Article 1 -->
        <article class="interactive-card surface-panel p-6 rounded-3xl flex flex-col justify-between space-y-4">
            <div class="space-y-3">
                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black bg-coral/10 text-coral">نورولوژی و زبان</span>
                <h2 class="text-base font-black text-ink leading-6">
                    کالبدشناسی ناحیه بروکا و مقایسه بالینی آفازی حرکتی و حسی
                </h2>
                <p class="text-xs text-broca-slate leading-6">
                    بررسی نواحی ۴۴ و ۴۵ برودمن، مسیرهای ارتباطی کورتیکال و تفاوت‌های بالینی بیماران مبتلا به آفازی بیانی و درکی.
                </p>
            </div>
            <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                <span class="text-broca-slate">دکتر مریم حسینی</span>
                <a href="{{ route('blog.show', 'broca-area-and-aphasia') }}" class="text-coral font-black hover:underline">مطالعه مقاله ←</a>
            </div>
        </article>

        <!-- Article 2 -->
        <article class="interactive-card surface-panel p-6 rounded-3xl flex flex-col justify-between space-y-4">
            <div class="space-y-3">
                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black bg-sun text-ink">فیزیولوژی قلب</span>
                <h2 class="text-base font-black text-ink leading-6">
                    تفسیر الکتروفیزیولوژی نوار قلب و مکانیسم پتانسیل عمل میوکارد
                </h2>
                <p class="text-xs text-broca-slate leading-6">
                    چگونگی ارتباط امواج P، کمپلکس QRS و موج T با جریان‌های یونی سدیم، پتاسیم و کلسیم در فیبرهای هدایتی قلب.
                </p>
            </div>
            <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                <span class="text-broca-slate">دکتر سارا احمدی</span>
                <a href="{{ route('blog.show', 'ecg-electrophysiology') }}" class="text-coral font-black hover:underline">مطالعه مقاله ←</a>
            </div>
        </article>

        <!-- Article 3 -->
        <article class="interactive-card surface-panel p-6 rounded-3xl flex flex-col justify-between space-y-4">
            <div class="space-y-3">
                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black bg-teal/10 text-teal">متدهای یادگیری</span>
                <h2 class="text-base font-black text-ink leading-6">
                    چگونه الگوریتم SM-2 به ماندگاری همیشگی مطالب پزشکی کمک می‌کند؟
                </h2>
                <p class="text-xs text-broca-slate leading-6">
                    بررسی منحنی فراموشی ابینگهاوس و تأثیر مرورهای فاصله‌دار در تثبیت آناتومی و فارماکولوژی در حافظه بلندمدت.
                </p>
            </div>
            <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                <span class="text-broca-slate">دکتر رضا کریمی</span>
                <a href="{{ route('blog.show', 'sm2-spaced-repetition-medicine') }}" class="text-coral font-black hover:underline">مطالعه مقاله ←</a>
            </div>
        </article>
    </div>
</section>
@endsection
