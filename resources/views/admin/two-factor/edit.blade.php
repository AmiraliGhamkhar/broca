@extends('layouts.app')

@section('title', 'امنیت و تأیید دو مرحله‌ای مدیران — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
            <div>
                <h2 class="text-2xl font-bold text-ink">امنیت احراز هویت دو مرحله‌ای (TOTP 2FA)</h2>
                <p class="text-xs text-muted mt-1">حفاظت از پنل مدیریت با نرم‌افزارهای احرازگر استاندارد (Google Authenticator، Microsoft Authenticator و...)</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->hasConfirmedTwoFactor() ? 'bg-teal/15 text-teal' : 'bg-rausch/15 text-rausch' }}">
                {{ $user->hasConfirmedTwoFactor() ? 'ورود دومرحله‌ای فعال است ✓' : 'غیرفعال' }}
            </span>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('recovery_codes'))
            <div class="mt-6 rounded-2xl border-2 border-teal bg-teal/5 p-6 space-y-4">
                <h3 class="text-sm font-bold text-teal">کدهای بازیابی یک‌بارمصرف (Recovery Codes)</h3>
                <p class="text-xs text-ink leading-5">این کدها را در مکانی امن کپی و نگهداری کنید. در صورت گم شدن گوشی، با این کدها می‌توانید وارد شوید.</p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 font-mono text-xs font-bold bg-white p-4 rounded-xl border border-teal/30" dir="ltr">
                    @foreach (session('recovery_codes') as $code)
                        <div class="p-2 bg-surface-soft rounded text-center">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! $user->hasConfirmedTwoFactor())
            @if ($user->totp_secret)
                <div class="mt-6 p-6 rounded-2xl bg-white border border-hairline-soft space-y-4">
                    <h3 class="text-sm font-bold text-ink">گام ۱: کلید زیر را در اپلیکیشن Authenticator وارد کنید</h3>
                    <div class="p-3.5 rounded-xl bg-ink text-white font-mono text-center text-sm font-bold tracking-widest" dir="ltr">
                        {{ chunk_split($user->totp_secret, 4, ' ') }}
                    </div>
                    @if ($otpauthUri)
                        <p class="text-[11px] text-muted font-mono break-all" dir="ltr">{{ $otpauthUri }}</p>
                    @endif

                    <h3 class="text-sm font-bold text-ink pt-3">گام ۲: کد ۶ رقمی تولید شده را وارد کنید</h3>
                    <form method="post" action="{{ route('admin.two-factor.enable') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="enable_code" class="block text-xs font-bold text-ink mb-1.5">کد تأیید ۶ رقمی</label>
                            <input id="enable_code" type="text" name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required placeholder="123456"
                                   class="w-full max-w-xs p-3 rounded-xl border border-ink/20 text-center text-xl font-mono font-bold tracking-widest bg-white" dir="ltr">
                        </div>
                        <button type="submit" class="rounded-full bg-ink px-6 py-2.5 text-xs font-bold text-white hover:bg-rausch transition-all">
                            تأیید و فعال‌سازی ورود دومرحله‌ای
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-6 p-8 rounded-2xl bg-surface-soft border border-hairline-soft text-center space-y-4">
                    <p class="text-sm font-bold text-muted">برای امنیت پنل مدیریت، احراز هویت دو مرحله‌ای را فعال کنید.</p>
                    <form method="post" action="{{ route('admin.two-factor.start') }}">
                        @csrf
                        <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-bold text-white hover:bg-rausch transition-all">
                            شروع راه‌اندازی 2FA
                        </button>
                    </form>
                </div>
            @endif
        @else
            <div class="mt-6 p-6 rounded-2xl bg-teal/5 border border-teal/20 space-y-4">
                <p class="text-xs font-bold text-teal leading-5">حساب شما به صورت ایمن با ورود دومرحله‌ای محافظت می‌شود. در هر بار ورود به پنل ادمین کد ۶ رقمی درخواست خواهد شد.</p>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-6 rounded-2xl bg-white border border-hairline-soft space-y-4">
                    <h3 class="text-sm font-bold text-ink">کدهای بازیابی جدید</h3>
                    <p class="text-xs text-muted leading-5">
                        با ساخت کدهای جدید، همه کدهای بازیابی قبلی بی‌اعتبار می‌شوند. کدهای تازه فقط یک بار نمایش داده می‌شوند.
                    </p>
                    <form method="post" action="{{ route('admin.two-factor.recovery-codes') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-ink/20 px-6 py-2.5 text-xs font-bold text-ink hover:bg-surface-soft transition-all">
                            ساخت کدهای بازیابی جدید
                        </button>
                    </form>
                </div>

                <div class="p-6 rounded-2xl bg-white border border-hairline-soft space-y-4">
                    <h3 class="text-sm font-bold text-rausch">غیرفعال‌سازی ورود دومرحله‌ای</h3>
                    <p class="text-xs text-muted leading-5">
                        برای غیرفعال‌سازی، کد ۶ رقمی فعلی اپلیکیشن احرازگر را وارد کنید. کلید و کدهای بازیابی پاک می‌شوند.
                    </p>
                    <form method="post" action="{{ route('admin.two-factor.disable') }}" class="space-y-3">
                        @csrf
                        <label for="disable_code" class="block text-xs font-bold text-ink">کد تأیید ۶ رقمی</label>
                        <input id="disable_code" type="text" name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required placeholder="123456"
                               class="w-full p-3 rounded-xl border border-ink/20 text-center text-lg font-mono font-bold tracking-widest bg-white" dir="ltr">
                        <button type="submit" class="rounded-full bg-rausch px-6 py-2.5 text-xs font-bold text-white hover:bg-rausch-active transition-all">
                            غیرفعال‌سازی 2FA
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
