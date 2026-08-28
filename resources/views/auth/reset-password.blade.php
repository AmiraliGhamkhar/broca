@extends('layouts.app')

@section('title', 'تعیین گذرواژهٔ جدید — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-lg px-4 sm:px-6 py-16 sm:py-24">
    <div class="form-panel p-8 sm:p-10 shadow-lg">
        <div class="text-center space-y-2 pb-6 border-b border-broca-sand">
            <h1 class="text-2xl sm:text-3xl font-black text-ink">تعیین گذرواژهٔ جدید</h1>
            <p class="text-xs text-broca-slate">گذرواژه جدید و امنی برای حساب خود وارد کنید.</p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}" />

            <div>
                <label for="email" class="block text-xs font-black text-ink mb-1">آدرس ایمیل</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email ?? '') }}" required dir="ltr"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80">
            </div>

            <div>
                <label for="password" class="block text-xs font-black text-ink mb-1">گذرواژهٔ جدید</label>
                <input type="password" id="password" name="password" required dir="ltr" placeholder="••••••••"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-black text-ink mb-1">تکرار گذرواژهٔ جدید</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required dir="ltr" placeholder="••••••••"
                       class="w-full p-3.5 rounded-xl border border-ink/20 text-xs font-medium bg-white/80">
            </div>

            <button type="submit" class="w-full mt-2 rounded-full bg-ink py-4 font-black text-cream text-xs hover:bg-coral transition-all shadow-md">
                ذخیره گذرواژه جدید و ورود
            </button>
        </form>
    </div>
</section>
@endsection
