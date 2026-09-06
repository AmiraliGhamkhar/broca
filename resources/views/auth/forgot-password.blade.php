@extends('layouts.app')

@section('title', 'بازیابی گذرواژه — ' . __('app.name'))
@section('meta_description', 'درخواست لینک بازیابی گذرواژه برای حساب بروکا از طریق ایمیل ثبت‌شده.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-5 space-y-6">
            <span class="sr-only">بازیابی امن حساب</span>
            <div class="section-intro">
                <h1 class="section-title mt-4">بازیابی گذرواژه</h1>
                <p class="section-copy">اگر گذرواژه را فراموش کرده‌اید، با وارد کردن ایمیل ثبت‌شده لینک بازیابی برای شما ارسال می‌شود تا بدون مراجعه پشتیبانی دوباره به حساب دسترسی داشته باشید.</p>
            </div>

            <div class="editorial-card is-soft">
                <div class="trust-list">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>فرآیند شفاف و قابل پیش‌بینی</strong>
                            <span>تنها ایمیل ثبت‌شده لازم است و پس از دریافت لینک، می‌توانید گذرواژه جدید تعیین کنید.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="document" class="size-5" /></span>
                        <div>
                            <strong>بدون دسترسی عمومی به اطلاعات</strong>
                            <span>هیچ محتوای آموزشی، اشتراک یا داده شخصی از این صفحه نمایش داده نمی‌شود؛ فقط مسیر بازیابی حساب فراهم است.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="form-panel p-8 sm:p-10">
                <div class="flex items-center gap-3 pb-6 border-b border-hairline-soft">
                    <span class="icon-frame-dark icon-frame-lg"><x-ui.icon name="shield" class="size-5" /></span>
                    <div>
                        <h2 class="text-2xl font-black text-ink">درخواست لینک بازیابی</h2>
                        <p class="text-xs text-muted mt-1">ایمیل حساب خود را وارد کنید؛ لینک تغییر گذرواژه برای شما ارسال خواهد شد.</p>
                    </div>
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
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus dir="ltr" autocomplete="email"
                               placeholder="doctor@example.com"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                    </div>

                    <button type="submit" class="button-primary w-full justify-center">
                        <x-ui.icon name="document" class="size-4" />
                        ارسال لینک بازیابی به ایمیل
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-hairline-soft text-center text-xs">
                    <span class="text-muted">رمز عبور را به خاطر آوردید؟</span>
                    <a href="{{ route('login') }}" class="text-rausch font-bold underline mr-1 hover:text-ink">ورود به حساب</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
