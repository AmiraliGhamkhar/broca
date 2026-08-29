@extends('layouts.app')

@section('title', 'ثبت‌نام و ساخت حساب کاربری — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-lg px-4 sm:px-6 py-14 sm:py-20">
    <div class="form-panel p-8 sm:p-10">
        <div class="text-center space-y-2 pb-6 border-b border-hairline-soft">
            <span class="grid size-12 place-items-center rounded-2xl bg-rausch text-white font-bold text-xl mx-auto shadow-md">
                +
            </span>
            <h1 class="font-display text-2xl sm:text-3xl text-ink">ساخت حساب کاربری</h1>
            <p class="text-xs text-muted">شروع رایگان آموزش پزشکی؛ دسترسی به دروس نمونه و فلش‌کارت‌ها</p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-bold text-ink mb-1">نام و نام خانوادگی (نمایشی)</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                       placeholder="مثال: علی احمدی"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-bold bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-bold text-ink mb-1">آدرس ایمیل</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required dir="ltr"
                           placeholder="doctor@example.com"
                           class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-ink mb-1">شمارهٔ همراه (ایران)</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required dir="ltr"
                           placeholder="09123456789"
                           class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-bold text-ink mb-1">گذرواژه (حداقل ۸ کاراکتر)</label>
                    <input type="password" id="password" name="password" required dir="ltr"
                           placeholder="••••••••"
                           class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-ink mb-1">تکرار گذرواژه</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required dir="ltr"
                           placeholder="••••••••"
                           class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-start gap-2.5 text-xs text-ink/80 leading-5 cursor-pointer">
                    <input type="checkbox" name="consent" value="1" required class="rounded border-ink/20 size-4 text-rausch mt-0.5 shrink-0">
                    <span>
                        با ساخت حساب،
                        <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="text-rausch underline font-bold">شرایط استفاده</a>،
                        <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="text-rausch underline font-bold">حریم خصوصی</a>
                        و
                        <a href="{{ route('legal.show', 'medical-disclaimer') }}" target="_blank" class="text-rausch underline font-bold">بیانیه مسئولیت پزشکی</a>
                        بروکا را می‌پذیرم.
                    </span>
                </label>
            </div>

            <button type="submit" class="w-full mt-3 rounded-full bg-ink py-4 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                ساخت حساب و شروع یادگیری
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-hairline-soft text-center text-xs">
            <span class="text-muted">قبلاً ثبت‌نام کرده‌اید؟</span>
            <a href="{{ route('login') }}" class="text-rausch font-bold underline mr-1 hover:text-ink">
                ورود به حساب کاربری
            </a>
        </div>
    </div>
</section>
@endsection
