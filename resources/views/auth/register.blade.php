@extends('layouts.app')

@section('title', 'ثبت‌نام و ساخت حساب کاربری — ' . __('app.name'))
@section('meta_description', 'ساخت حساب در بروکا برای شروع رایگان آموزش پزشکی و دسترسی به نمونه‌درس‌ها، جزوات و مرور هوشمند.')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-5 space-y-6">
            <span class="eyebrow">شروع حرفه‌ای با حساب رایگان</span>
            <div class="section-intro">
                <h1 class="section-title mt-4">ساخت حساب در بروکا</h1>
                <p class="section-copy">حساب کاربری شما نقطه شروع مسیر یادگیری، مدیریت دسترسی، ذخیره پیشرفت و سنجش کیفیت دوره‌ها پیش از خرید اشتراک است.</p>
            </div>

            <div class="editorial-card is-soft">
                <h2 class="text-base font-extrabold text-ink">با ثبت‌نام چه چیزی فعال می‌شود؟</h2>
                <div class="trust-list mt-5">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="play" class="size-5" /></span>
                        <div>
                            <strong>نمونه‌درس و تجربه واقعی پلتفرم</strong>
                            <span>پیش از خرید می‌توانید با کیفیت تجربه آموزشی، مسیر مشاهده و ساختار دوره‌ها آشنا شوید.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="refresh" class="size-5" /></span>
                        <div>
                            <strong>مرور هوشمند و ثبت پیشرفت</strong>
                            <span>وضعیت یادگیری، مرور کارت‌ها و نتایج آزمون‌ها به حساب شما وابسته است و پس از ثبت‌نام قابل پیگیری می‌شود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>پذیرش آگاهانه قوانین</strong>
                            <span>شرایط استفاده، حریم خصوصی و بیانیه مسئولیت پزشکی به‌وضوح کنار فرایند ثبت‌نام نمایش داده می‌شوند.</span>
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
                        <h2 class="text-2xl font-black text-ink">ساخت حساب کاربری</h2>
                        <p class="text-xs text-muted mt-1">اطلاعات پایه را وارد کنید تا حساب یادگیری شما ساخته شود.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('register') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-bold text-ink mb-1">نام و نام خانوادگی</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                               placeholder="مثال: علی احمدی"
                               class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-bold bg-white">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-bold text-ink mb-1">آدرس ایمیل</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required dir="ltr" autocomplete="email"
                                   placeholder="doctor@example.com"
                                   class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-ink mb-1">شمارهٔ همراه</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required dir="ltr" autocomplete="tel"
                                   placeholder="09123456789"
                                   class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-ink mb-1">گذرواژه</label>
                            <input type="password" id="password" name="password" required dir="ltr" autocomplete="new-password"
                                   placeholder="حداقل ۸ کاراکتر"
                                   class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-ink mb-1">تکرار گذرواژه</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required dir="ltr" autocomplete="new-password"
                                   placeholder="تکرار گذرواژه"
                                   class="w-full p-3.5 rounded-xl border border-ink/20 text-sm font-medium bg-white">
                        </div>
                    </div>

                    <div class="meta-card is-soft">
                        <label class="flex items-start gap-2.5 text-xs text-ink/80 leading-6 cursor-pointer">
                            <input type="checkbox" name="consent" value="1" required class="rounded border-ink/20 size-4 text-rausch mt-0.5 shrink-0">
                            <span>
                                با ساخت حساب،
                                <a href="{{ route('legal.show', 'terms') }}" target="_blank" class="text-rausch underline font-bold">شرایط استفاده</a>،
                                <a href="{{ route('legal.show', 'privacy') }}" target="_blank" class="text-rausch underline font-bold">حریم خصوصی</a>
                                و
                                <a href="{{ route('legal.show', 'medical-disclaimer') }}" target="_blank" class="text-rausch underline font-bold">بیانیه مسئولیت پزشکی</a>
                                را با آگاهی می‌پذیرم.
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="button-primary w-full justify-center">
                        <x-ui.icon name="graduation" class="size-4" />
                        ساخت حساب و شروع یادگیری
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-hairline-soft text-center text-xs">
                    <span class="text-muted">قبلاً ثبت‌نام کرده‌اید؟</span>
                    <a href="{{ route('login') }}" class="text-rausch font-bold underline mr-1 hover:text-ink">ورود به حساب کاربری</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
