@extends('layouts.app')

@section('title', 'تعیین گذرواژهٔ جدید — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-xl px-5 py-20 sm:px-8 lg:py-28">
    <p class="text-sm font-black text-coral">بازیابی گذرواژه</p>
    <h1 class="mt-4 text-4xl font-black sm:text-6xl">گذرواژهٔ جدید</h1>

    @if ($errors->any())<div class="mt-6 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-sm font-bold" role="alert">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('password.update') }}" class="mt-10 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />

        <label class="block">
            <span class="mb-2 block font-bold">ایمیل</span>
            <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" />
        </label>
        <label class="block">
            <span class="mb-2 block font-bold">گذرواژهٔ جدید</span>
            <input type="password" name="password" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" />
        </label>
        <label class="block">
            <span class="mb-2 block font-bold">تکرار گذرواژهٔ جدید</span>
            <input type="password" name="password_confirmation" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" />
        </label>
        <button class="w-full rounded-full bg-ink px-6 py-4 font-black text-cream">ثبت گذرواژهٔ جدید</button>
    </form>
</section>
@endsection
