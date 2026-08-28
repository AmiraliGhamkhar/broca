@extends('layouts.app')

@section('title', 'ورود به حساب کاربری — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-lg px-4 sm:px-6 py-16 sm:py-24">
    <div class="form-panel p-8 sm:p-10 shadow-lg">
        <div class="text-center space-y-2 pb-6 border-b border-broca-sand">
            <span class="grid size-12 place-items-center rounded-2xl bg-ink text-sun font-black text-xl mx-auto shadow-md">
                ب
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-ink">ورود به آکادمی بروکا</h1>
            <p class="text-xs text-broca-slate">برای دسترسی به دوره‌ها، فلش‌کارت‌ها و فضای یادگیری وارد شوید.</p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="identifier" class="block text-xs font-black text-ink mb-1.5">ایمیل یا شمارهٔ همراه (مانند 09123456789)</label>
                <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus
                       placeholder="ایمیل یا شماره موبایل" dir="ltr"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-coral focus:ring-1 focus:ring-coral transition-all">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-black text-ink">گذرواژه</label>
                    <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-coral hover:underline">فراموشی رمز عبور؟</a>
                </div>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••" dir="ltr"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-coral focus:ring-1 focus:ring-coral transition-all">
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 text-xs font-bold text-ink cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="rounded border-ink/20 size-4 text-coral">
                    <span>مرا به خاطر بسپار</span>
                </label>
            </div>

            <button type="submit" class="w-full mt-2 rounded-full bg-ink py-4 font-black text-cream text-xs hover:bg-coral transition-all shadow-md">
                ورود به پنل کاربری
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-broca-sand text-center text-xs">
            <span class="text-broca-slate">هنوز حساب کاربری نساخته‌اید؟</span>
            <a href="{{ route('register') }}" class="text-coral font-black underline mr-1 hover:text-ink">
                ثبت‌نام رایگان در بروکا
            </a>
        </div>
    </div>
</section>
@endsection
