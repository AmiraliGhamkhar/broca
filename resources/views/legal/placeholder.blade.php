@extends('layouts.app')

@section('title', $heading . ' — ' . __('app.name'))

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="space-y-3 pb-8 border-b border-hairline-soft">
        <span class="text-xs font-bold text-rausch">اسناد حقوقی و تعهدات بالینی</span>
        <h1 class="text-3xl sm:text-4xl font-bold text-ink">{{ $heading }}</h1>
        <p class="text-xs text-muted">آخرین به‌روزرسانی: ۱۴۰۳/۰۶/۰۱ · نسخه: {{ config('broca.terms_version') }}</p>
    </div>

    <div class="mt-8 surface-panel p-8 sm:p-10 text-xs sm:text-sm text-ink/85 leading-8 space-y-6">
        @if ($heading === 'بیانیهٔ پزشکی' || request()->is('medical-disclaimer'))
            <div class="p-4 rounded-2xl bg-rausch/10 border border-coral/30 text-rausch font-bold space-y-2">
                <span class="text-base block">⚖️ سلب مسئولیت صریح بالینی (YMYL Medical Disclaimer)</span>
                <p>
                    تمامی محتواهای چندرسانه‌ای، متون، آزمون‌ها و مقالات منتشرشده در وب‌سایت بروکا منحصراً با هدف ارتقای دانش تئوری و آمادگی دانشجویان رشته‌های پزشکی، دندانپزشکی، داروسازی و پیراپزشکی تولید شده‌اند.
                </p>
            </div>
            <h2 class="text-base font-bold text-ink">۱. عدم ارائه مشاوره درمانی</h2>
            <p>
                هیچ بخشی از محتوای این سامانه نباید به عنوان مشاوره پزشکی، تشخیص بیماری، پروتکل تجویز دارو یا جایگزین مراجعه به پزشک متخصص تلقی گردد.
            </p>
            <h2 class="text-base font-bold text-ink">۲. بازبینی علمی اعضای هیئت علمی</h2>
            <p>
                دوره‌ها و مقالات با استناد به منابع معتبر جهانی (از جمله گایتون، هاریسون، گری و نتر) توسط اساتید تألیف و توسط متخصصین مستقل بازبینی می‌شوند، اما علم پزشکی پیوسته در حال تغییر است.
            </p>
        @elseif ($heading === 'حریم خصوصی' || request()->is('privacy'))
            <h2 class="text-base font-bold text-ink">۱. اطلاعات جمع‌آوری شده</h2>
            <p>
                بروکا تنها اطلاعات ضروری شامل نام، آدرس ایمیل و شماره همراه را جهت احراز هویت، فعال‌سازی اشتراک و ذخیره روند مرور فلش‌کارت‌ها جمع‌آوری می‌کند.
            </p>
            <h2 class="text-base font-bold text-ink">۲. امنیت پرداخت‌ها</h2>
            <p>
                اطلاعات حساس بانکی نظیر شماره کارت و رمز دوم در درگاه پرداخت شاپرک (زرین‌پال) پردازش شده و در سرورهای بروکا ذخیره نمی‌شوند.
            </p>
        @elseif ($heading === 'شرایط استفاده' || request()->is('terms'))
            <h2 class="text-base font-bold text-ink">۱. مالکیت فکری و حق نشر</h2>
            <p>
                تمام ویدیوها، متون و جزوات اختصاصی محفوظ و متعلق به آکادمی بروکا است. هرگونه بازنشر، فروش یا انتشار غیرمجاز فایل‌ها پیگرد قانونی دارد.
            </p>
            <h2 class="text-base font-bold text-ink">۲. اشتراک‌های آموزشی</h2>
            <p>
                پلن‌های اشتراک دارای مدت زمان مشخص (یک‌ماهه و سه‌ماهه) بوده و پس از اتمام دوره نیازمند تمدید توسط کاربر می‌باشند.
            </p>
        @elseif ($heading === 'تماس با بروکا' || request()->is('contact'))
            <h2 class="text-base font-bold text-ink">راه‌های ارتباط و پشتیبانی</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div class="p-4 rounded-2xl bg-surface-soft border border-hairline-soft">
                    <span class="font-bold text-ink block">ایمیل پشتیبانی:</span>
                    <span class="font-mono text-xs text-muted" dir="ltr">support@broca.test</span>
                </div>
                <div class="p-4 rounded-2xl bg-surface-soft border border-hairline-soft">
                    <span class="font-bold text-ink block">ساعات پاسخگویی:</span>
                    <span class="text-xs text-muted">شنبه تا چهارشنبه ۹ الی ۱۷</span>
                </div>
            </div>
        @else
            <p>محتوای این بخش بر اساس قوانین جمهوری اسلامی ایران و اصول اخلاق پزشکی مدون شده است.</p>
        @endif
    </div>
</section>
@endsection
