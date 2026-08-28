@extends('layouts.app')

@section('title', 'امنیت و تأیید دو مرحله‌ای مدیران — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
            <div>
                <h2 class="text-2xl font-black text-ink">امنیت احراز هویت دو مرحله‌ای (TOTP 2FA)</h2>
                <p class="text-xs text-broca-slate mt-1">حفاظت از پنل مدیریت با نرم‌افزارهای احرازگر استاندارد (Google Authenticator، Microsoft Authenticator و...)</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-black {{ $user->hasConfirmedTwoFactor() ? 'bg-teal/15 text-teal' : 'bg-coral/15 text-coral' }}">
                {{ $user->hasConfirmedTwoFactor() ? 'ورود دومرحله‌ای فعال است ✓' : 'غیرفعال' }}
            </span>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('recovery_codes'))
            <div class="mt-6 rounded-2xl border-2 border-teal bg-teal/5 p-6 space-y-4">
                <h3 class="text-sm font-black text-teal">کدهای بازیابی یک‌بارمصرف (Recovery Codes)</h3>
                <p class="text-xs text-ink leading-5">این کدها را در مکانی امن کپی و نگهداری کنید. در صورت گم شدن گوشی، با این کدها می‌توانید وارد شوید.</p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 font-mono text-xs font-bold bg-white p-4 rounded-xl border border-teal/30" dir="ltr">
                    @foreach (session('recovery_codes') as $code)
                        <div class="p-2 bg-ink/5 rounded text-center">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! $user->hasConfirmedTwoFactor())
            @if ($user->totp_secret)
                <div class="mt-6 p-6 rounded-2xl bg-white/70 border border-broca-sand space-y-4">
                    <h3 class="text-sm font-black text-ink">گام ۱: کلید زیر را در اپلیکیشن Authenticator وارد کنید</h3>
                    <div class="p-3.5 rounded-xl bg-ink text-sun font-mono text-center text-sm font-black tracking-widest" dir="ltr">
                        {{ chunk_split($user->totp_secret, 4, ' ') }}
                    </div>
                    @if ($otpauthUri)
                        <p class="text-[11px] text-broca-slate font-mono break-all" dir="ltr">{{ $otpauthUri }}</p>
                    @endif

                    <h3 class="text-sm font-black text-ink pt-3">گام ۲: کد ۶ رقمی تولید شده را وارد کنید</h3>
                    <form method="post" action="{{ route('admin.two-factor.enable') }}" class="space-y-4">
                        @csrf
                        <div>
                            <input type="text" name="code" maxlength="6" inputmode="numeric" required placeholder="123456"
                                   class="w-full max-w-xs p-3 rounded-xl border border-ink/20 text-center text-xl font-mono font-black tracking-widest bg-white" dir="ltr">
                        </div>
                        <button type="submit" class="rounded-full bg-ink px-6 py-2.5 text-xs font-black text-cream hover:bg-coral transition-all">
                            تأیید و فعال‌سازی ورود دومرحله‌ای
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-6 p-8 rounded-2xl bg-white/60 border border-broca-sand text-center space-y-4">
                    <p class="text-sm font-bold text-broca-slate">برای امنیت پنل مدیریت، احراز هویت دو مرحله‌ای را فعال کنید.</p>
                    <form method="post" action="{{ route('admin.two-factor.start') }}">
                        @csrf
                        <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-black text-cream hover:bg-coral transition-all">
                            شروع راه‌اندازی 2FA
                        </button>
                    </form>
                </div>
            @endif
        @else
            <div class="mt-6 p-6 rounded-2xl bg-teal/5 border border-teal/20 space-y-4">
                <p class="text-xs font-bold text-teal leading-5">حساب شما به صورت ایمن با ورود دومرحله‌ای محافظت می‌شود. در هر بار ورود به پنل ادمین کد ۶ رقمی درخواست خواهد شد.</p>
            </div>
        @endif
    </div>
</section>
@endsection
