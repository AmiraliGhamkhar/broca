@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-xl px-5 py-20 sm:px-8 lg:py-28">
    <p class="text-sm font-black text-coral">حساب بروکا</p>
    <h1 class="mt-4 text-4xl font-black sm:text-6xl">شروع مسیر یادگیری</h1>
    <p class="mt-5 leading-8 text-ink/65">برای ساخت حساب، ایمیل و شمارهٔ همراه خود را وارد کنید. ایمیل برای دسترسی به یادگیری باید تأیید شود.</p>
    @if ($errors->any())<div class="mt-6 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-sm font-bold">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('register') }}" class="mt-10 space-y-5">
        @csrf
        <label class="block"><span class="mb-2 block font-bold">نام نمایشی</span><input name="name" value="{{ old('name') }}" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="block"><span class="mb-2 block font-bold">ایمیل</span><input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="block"><span class="mb-2 block font-bold">شمارهٔ همراه</span><input name="phone" value="{{ old('phone') }}" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="block"><span class="mb-2 block font-bold">گذرواژه</span><input type="password" name="password" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="block"><span class="mb-2 block font-bold">تکرار گذرواژه</span><input type="password" name="password_confirmation" required class="w-full rounded-2xl border border-ink/20 bg-transparent px-4 py-3" /></label>
        <label class="flex items-start gap-3 text-sm font-medium leading-7"><input type="checkbox" name="consent" value="1" required class="mt-2" />شرایط استفاده، حریم خصوصی و بیانیهٔ پزشکی را می‌پذیرم.</label>
        <button class="w-full rounded-full bg-ink px-6 py-4 font-black text-cream">ساخت حساب</button>
    </form>
</section>
@endsection
