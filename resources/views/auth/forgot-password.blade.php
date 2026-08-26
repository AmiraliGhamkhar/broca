@extends('layouts.app')

@section('title', 'بازیابی گذرواژه — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-xl px-5 py-20 sm:px-8 lg:py-28">
    <p class="text-sm font-black text-coral">بازیابی گذرواژه</p>
    <h1 class="mt-4 text-4xl font-black sm:text-6xl">گذرواژه را فراموش کرده‌ای؟</h1>
    <p class="mt-5 leading-8 text-ink/65">ایمیل حساب خود را وارد کن؛ لینک بازیابی برایت ارسال می‌شود.</p>

    @if ($errors->any())<div class="mt-6 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-sm font-bold" role="alert">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('password.email') }}" class="mt-10 space-y-5">
        @csrf
        <label class="block">
            <span class="mb-2 block font-bold">ایمیل</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" />
        </label>
        <button class="w-full rounded-full bg-ink px-6 py-4 font-black text-cream">ارسال لینک بازیابی</button>
    </form>

    <p class="mt-8 text-sm font-bold text-ink/60">به یاد آوردی؟ <a class="text-coral underline" href="{{ route('login') }}">وارد شو</a></p>
</section>
@endsection
