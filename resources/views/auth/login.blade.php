@extends('layouts.app')

@section('title', 'ورود به حساب کاربری — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-lg px-4 sm:px-6 py-16 sm:py-24">
    <div class="form-panel p-8 sm:p-10">
        <div class="text-center space-y-2 pb-6 border-b border-hairline-soft">
            <span class="grid size-12 place-items-center rounded-2xl bg-rausch text-white font-bold text-xl mx-auto shadow-md">
                ب
            </span>
            <h1 class="font-display text-2xl sm:text-3xl text-ink">ورود به آکادمی بروکا</h1>
            <p class="text-xs text-muted">برای دسترسی به دوره‌ها، فلش‌کارت‌ها و فضای یادگیری وارد شوید.</p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="identifier" class="block text-xs font-bold text-ink mb-1.5">ایمیل یا شمارهٔ همراه (مانند 09123456789)</label>
                <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus
                       placeholder="ایمیل یا شماره موبایل" dir="ltr"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold text-ink">گذرواژه</label>
                    <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-rausch hover:underline">فراموشی رمز عبور؟</a>
                </div>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••" dir="ltr"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 text-xs font-bold text-ink cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="rounded border-ink/20 size-4 text-rausch">
                    <span>مرا به خاطر بسپار</span>
                </label>
            </div>

            <button type="submit" class="w-full mt-2 rounded-full bg-rausch py-4 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                ورود به پنل کاربری
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-hairline-soft text-center text-xs">
            <span class="text-muted">هنوز حساب کاربری نساخته‌اید؟</span>
            <a href="{{ route('register') }}" class="text-rausch font-bold underline mr-1 hover:text-ink">
                ثبت‌نام رایگان در بروکا
            </a>
        </div>
    </div>
</section>
@endsection
