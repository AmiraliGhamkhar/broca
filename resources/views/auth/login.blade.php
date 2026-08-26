@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-xl px-5 py-20 sm:px-8 lg:py-28">
    <p class="text-sm font-black text-coral">ورود به بروکا</p>
    <h1 class="mt-4 text-4xl font-black sm:text-6xl">ادامهٔ مسیر</h1>
    @if ($errors->any())<div class="mt-6 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-sm font-bold">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('login') }}" class="mt-10 space-y-5">
        @csrf
        <label class="block"><span class="mb-2 block font-bold">ایمیل یا شمارهٔ همراه</span><input name="identifier" value="{{ old('identifier') }}" required autofocus class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="block"><span class="mb-2 block font-bold">گذرواژه</span><input type="password" name="password" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="flex items-center gap-3 text-sm font-bold"><input type="checkbox" name="remember" value="1" /> مرا به خاطر بسپار</label>
        <button class="w-full rounded-full bg-ink px-6 py-4 font-black text-cream">ورود</button>
    </form>
    <p class="mt-6 text-sm font-bold text-ink/60"><a class="text-coral underline" href="{{ route('password.request') }}">گذرواژه‌ات را فراموش کرده‌ای؟</a></p>
    <p class="mt-8 text-sm font-bold text-ink/60">حساب نداری؟ <a class="text-coral underline" href="{{ route('register') }}">ثبت‌نام کن</a></p>
</section>
@endsection
