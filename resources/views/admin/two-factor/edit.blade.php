@extends('layouts.app')

@section('title', 'امنیت حساب مدیر — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-2xl px-5 py-20 sm:px-8">
    <p class="text-sm font-black text-coral">امنیت مدیران</p>
    <h1 class="mt-4 text-4xl font-black sm:text-5xl">ورود دومرحله‌ای (TOTP)</h1>

    @if (session('recovery_codes'))
        <div class="mt-8 rounded-[2rem] border-2 border-teal bg-teal/10 p-6" role="status" aria-live="polite">
            <h2 class="text-xl font-black text-teal">کدهای بازیابی — فقط همین یک بار</h2>
            <p class="mt-2 text-sm leading-7">این کدها را در جای امنی ذخیره کنید. هر کد یک بار قابل استفاده است و دوباره نمایش داده نمی‌شود.</p>
            <ul class="mt-4 grid grid-cols-2 gap-2 text-lg font-black" dir="ltr">
                @foreach (session('recovery_codes') as $code)
                    <li class="rounded-xl bg-cream px-3 py-2 text-center">{{ $code }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($user->hasConfirmedTwoFactor())
        <div class="mt-8 rounded-[2rem] border border-teal/40 bg-teal/10 p-6">
            <p class="font-black text-teal">ورود دومرحله‌ای فعال است ✓</p>
            <p class="mt-2 text-sm leading-7 text-ink/70">هر بار ورود، کد ۶ رقمی از اپلیکیشن احرازگر پرسیده می‌شود. {{ count($user->recoveryCodes()) }} کد بازیابی باقی مانده است.</p>

            <form method="post" action="{{ route('admin.two-factor.disable') }}" class="mt-5 space-y-3">
                @csrf
                <label for="disable_code" class="block text-sm font-bold">برای غیرفعال‌سازی، کد فعلی را وارد کنید:</label>
                <input id="disable_code" name="code" type="text" inputmode="numeric" maxlength="6" required class="w-40 rounded-xl border border-ink/20 bg-transparent p-2 text-center font-black tracking-widest" dir="ltr">
                @error('code')<p class="text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
                <button type="submit" class="block rounded-full border border-coral px-5 py-2 text-sm font-black text-coral">غیرفعال‌سازی</button>
            </form>
        </div>
    @elseif ($user->totp_secret)
        <div class="mt-8 rounded-[2rem] border border-ink/15 p-6">
            <p class="font-black">مرحلهٔ ۱ — کلید را در اپلیکیشن احرازگر ثبت کن</p>
            <p class="mt-2 text-sm leading-7 text-ink/70">در Google Authenticator یا هر اپلیکیشن مشابه، گزینهٔ «افزودن کلید دستی» را بزن و این کلید را وارد کن:</p>
            <code class="mt-3 block overflow-x-auto rounded-xl bg-ink/5 p-3 text-lg font-black" dir="ltr">{{ $user->totp_secret }}</code>
            <p class="mt-3 text-xs text-ink/60" dir="ltr">{{ $otpauthUri }}</p>

            <form method="post" action="{{ route('admin.two-factor.enable') }}" class="mt-6 space-y-3">
                @csrf
                <p class="font-black">مرحلهٔ ۲ — کد ۶ رقمی تولیدشده را تأیید کن</p>
                <label for="enable_code" class="block text-sm font-bold">کد تأیید</label>
                <input id="enable_code" name="code" type="text" inputmode="numeric" maxlength="6" required class="w-40 rounded-xl border border-ink/20 bg-transparent p-2 text-center font-black tracking-widest" dir="ltr">
                @error('code')<p class="text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
                <button type="submit" class="block rounded-full bg-ink px-6 py-3 font-black text-cream">فعال‌سازی ورود دومرحله‌ای</button>
            </form>
        </div>
    @else
        <div class="mt-8 rounded-[2rem] border border-ink/15 p-6">
            <p class="leading-8 text-ink/70">حساب مدیر شما هنوز از ورود دومرحله‌ای محافظت نمی‌شود. با فعال‌سازی آن، حتی در صورت لو رفتن گذرواژه، دسترسی مدیران قابل سوءاستفاده نخواهد بود.</p>
            <form method="post" action="{{ route('admin.two-factor.start') }}" class="mt-5">
                @csrf
                <button type="submit" class="rounded-full bg-ink px-6 py-3 font-black text-cream">شروع فعال‌سازی</button>
            </form>
        </div>
    @endif

    <a href="{{ route('admin.dashboard') }}" class="mt-10 inline-block text-sm font-black text-coral underline">بازگشت به پیشخوان مدیریت</a>
</section>
@endsection
