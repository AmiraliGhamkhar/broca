@extends('layouts.app')

@section('title', 'ورود دومرحله‌ای — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-xl px-5 py-20 sm:px-8">
    <p class="text-sm font-black text-coral">امنیت مدیران</p>
    <h1 class="mt-4 text-4xl font-black sm:text-5xl">کد تأیید دومرحله‌ای را وارد کن</h1>
    <p class="mt-5 leading-8 text-ink/65">کد ۶ رقمی از اپلیکیشن احرازگر خود (Google Authenticator و مشابه آن) را وارد کنید. اگر به اپلیکیشن دسترسی نداری، از یکی از کدهای بازیابی استفاده کن.</p>

    <form method="post" action="{{ route('admin.two-factor.verify') }}" class="mt-10 space-y-5">
        @csrf
        <div>
            <label for="code" class="block text-sm font-bold">کد تأیید</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus
                   class="mt-1 w-full rounded-xl border border-ink/20 bg-transparent p-3 text-center text-2xl font-black tracking-[0.4em]" dir="ltr">
            @error('code')<p class="mt-2 text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="w-full rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">تأیید و ورود</button>
    </form>

    <details class="mt-8 rounded-2xl border border-ink/15 p-5">
        <summary class="cursor-pointer text-sm font-black">دسترسی به اپلیکیشن ندارم — استفاده از کد بازیابی</summary>
        <form method="post" action="{{ route('admin.two-factor.recover') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="recovery_code" class="block text-sm font-bold">کد بازیابی</label>
                <input id="recovery_code" name="recovery_code" type="text" required class="mt-1 w-full rounded-xl border border-ink/20 bg-transparent p-3 font-bold" dir="ltr" placeholder="XXXXX-XXXXX">
                @error('recovery_code')<p class="mt-2 text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full rounded-full border border-ink px-7 py-3 font-black">ورود با کد بازیابی</button>
            <p class="text-xs text-ink/60">هر کد بازیابی فقط یک بار قابل استفاده است.</p>
        </form>
    </details>

    <form method="post" action="{{ route('logout') }}" class="mt-8">
        @csrf
        <button type="submit" class="text-sm font-bold text-ink/60 underline">خروج از حساب</button>
    </form>
</section>
@endsection
