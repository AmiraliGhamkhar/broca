@extends('layouts.app')

@section('title', 'ورود به حساب کاربری — ' . __('app.name'))
@section('meta_description', 'ورود به حساب بروکا برای دسترسی به دوره‌ها، فلش‌کارت‌ها، آزمون‌ها و مسیر یادگیری پزشکی.')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-5 space-y-6">
            <span class="eyebrow">ورود امن به تجربه یادگیری</span>
            <div class="section-intro">
                <h1 class="section-title mt-4">ورود به حساب بروکا</h1>
                <p class="section-copy">برای ادامه دوره‌ها، مشاهده وضعیت اشتراک، مرور کارت‌ها و پیگیری پیشرفت آموزشی وارد حساب خود شوید.</p>
            </div>

            <div class="editorial-card is-soft">
                <div class="flex items-start gap-3">
                    <span class="icon-frame"><x-ui.icon name="shield" class="size-5" /></span>
                    <div>
                        <h2 class="text-base font-extrabold text-ink">چرا این صفحه باید اعتماد ایجاد کند؟</h2>
                        <p class="mt-2 text-sm leading-7 text-muted">ورود، دروازه دسترسی به محتوای شخصی، وضعیت مالی و پیشرفت آموزشی کاربر است. بنابراین باید ساده، واضح و بدون ابهام باشد.</p>
                    </div>
                </div>

                <div class="trust-list mt-5">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="graduation" class="size-5" /></span>
                        <div>
                            <strong>دسترسی به داشبورد یادگیری</strong>
                            <span>ادامه ویدیوها، مرور فاصله‌دار، آزمون‌ها و محتوای ذخیره‌شده از همینجا قابل پیگیری است.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="wallet" class="size-5" /></span>
                        <div>
                            <strong>نمایش شفاف وضعیت اشتراک</strong>
                            <span>پس از ورود، وضعیت پلن فعال، تاریخ انقضا و مسیر دسترسی شما به‌طور واضح نمایش داده می‌شود.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-7">
            <div class="form-panel p-8 sm:p-10">
                <div class="flex items-center gap-3 pb-6 border-b border-hairline-soft">
                    <span class="icon-frame-dark icon-frame-lg">ب</span>
                    <div>
                        <h2 class="text-2xl font-black text-ink">ورود به آکادمی بروکا</h2>
                        <p class="text-xs text-muted mt-1">ایمیل یا شماره همراه خود را وارد کنید تا به فضای آموزشی شخصی‌تان دسترسی پیدا کنید.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="identifier" class="block text-xs font-bold text-ink mb-1.5">ایمیل یا شمارهٔ همراه</label>
                        <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus autocomplete="username"
                               placeholder="ایمیل یا شماره موبایل" dir="ltr"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5 gap-3">
                            <label for="password" class="block text-xs font-bold text-ink">گذرواژه</label>
                            <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-rausch hover:underline">فراموشی رمز عبور؟</a>
                        </div>
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               placeholder="••••••••" dir="ltr"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <label class="flex items-center gap-2 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="remember" value="1" class="rounded border-ink/20 size-4 text-rausch">
                            <span>مرا به خاطر بسپار</span>
                        </label>
                        <span class="text-[11px] text-muted">ورود برای دسترسی به محتوای شخصی و گزارش یادگیری</span>
                    </div>

                    <button type="submit" class="button-primary w-full justify-center">
                        <x-ui.icon name="shield" class="size-4" />
                        ورود به پنل کاربری
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-hairline-soft text-center text-xs">
                    <span class="text-muted">هنوز حساب کاربری ندارید؟</span>
                    <a href="{{ route('register') }}" class="text-rausch font-bold underline mr-1 hover:text-ink">ثبت‌نام رایگان در بروکا</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
