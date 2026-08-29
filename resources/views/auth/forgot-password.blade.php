@extends('layouts.app')

@section('title', 'بازیابی گذرواژه — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-lg px-4 sm:px-6 py-16 sm:py-24">
    <div class="form-panel p-8 sm:p-10">
        <div class="text-center space-y-2 pb-6 border-b border-hairline-soft">
            <span class="grid size-12 place-items-center rounded-2xl bg-rausch text-white font-bold text-xl mx-auto shadow-md">
                🔑
            </span>
            <h1 class="font-display text-2xl sm:text-3xl text-ink">بازیابی گذرواژه</h1>
            <p class="text-xs text-muted">ایمیل حساب خود را وارد کنید؛ لینک تغییر رمز برای شما ارسال خواهد شد.</p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold text-ink mb-1.5">آدرس ایمیل ثبت‌شده</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus dir="ltr"
                       placeholder="doctor@example.com"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80 focus:bg-white focus:border-ink focus:ring-1 focus:ring-ink transition-all">
            </div>

            <button type="submit" class="w-full rounded-full bg-ink py-4 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                ارسال لینک بازیابی به ایمیل
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-hairline-soft text-center text-xs">
            <span class="text-muted">رمز عبور را به خاطر آوردید؟</span>
            <a href="{{ route('login') }}" class="text-rausch font-bold underline mr-1 hover:text-ink">
                ورود به حساب
            </a>
        </div>
    </div>
</section>
@endsection
