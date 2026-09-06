@extends('layouts.app')

@section('title', 'تعیین گذرواژهٔ جدید — ' . __('app.name'))
@section('meta_description', 'تعیین گذرواژه جدید برای حساب بروکا و بازگشت امن به فضای یادگیری.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-5 space-y-6">
            <span class="sr-only">بازگشت امن به حساب</span>
            <div class="section-intro">
                <h1 class="section-title mt-4">تعیین گذرواژهٔ جدید</h1>
                <p class="section-copy">در این مرحله گذرواژه جدیدی برای حساب خود انتخاب می‌کنید تا دوباره به دوره‌ها، پیشرفت آموزشی و فضای شخصی‌تان دسترسی داشته باشید.</p>
            </div>

            <div class="editorial-card is-soft">
                <div class="trust-list">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>بهتر است گذرواژه‌ای منحصربه‌فرد انتخاب کنید</strong>
                            <span>برای حفاظت از حساب، از رمز قوی و متفاوت با سایر سرویس‌ها استفاده کنید.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <strong>هدف: حفاظت از مسیر آموزشی شخصی شما</strong>
                            <span>دوره‌های ثبت‌نام‌شده، نتایج آزمون‌ها و وضعیت اشتراک به این حساب متصل هستند.</span>
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
                        <h2 class="text-2xl font-black text-ink">ذخیره گذرواژه جدید</h2>
                        <p class="text-xs text-muted mt-1">پس از ذخیره رمز جدید، می‌توانید دوباره وارد حساب خود شوید.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}" />

                    <div>
                        <label for="email" class="block text-xs font-bold text-ink mb-1">آدرس ایمیل</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email ?? '') }}" required dir="ltr" autocomplete="email"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-ink mb-1">گذرواژهٔ جدید</label>
                        <input type="password" id="password" name="password" required dir="ltr" autocomplete="new-password" placeholder="••••••••"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-ink mb-1">تکرار گذرواژهٔ جدید</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required dir="ltr" autocomplete="new-password" placeholder="••••••••"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                    </div>

                    <button type="submit" class="button-primary w-full justify-center">
                        <x-ui.icon name="shield" class="size-4" />
                        ذخیره گذرواژه جدید و ورود
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
