@extends('layouts.app')

@section('title', __('app.name') . ' — ' . __('app.tagline'))

@section('content')
    <!-- Hero Section -->
    <x-landing-hero />

    <!-- Section 1: Core Medical Specialties & Courses -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-b border-hairline-soft">
        <div class="text-right space-y-2 max-w-2xl">
            <span class="text-xs font-bold text-rausch">شاخه‌های جامع آموزش پزشکی</span>
            <h2 class="font-display text-2xl sm:text-3xl text-ink">سرفصل‌های علوم پایه و آمادگی آزمون‌های بالینی</h2>
            <p class="text-xs sm:text-sm text-muted leading-7">
                تمامی دروس توسط اساتید مجرب و با تمرکز بر مفاهیم کلیدی و تفکر بالینی تدوین و بازبینی شده‌اند.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1: Cardio -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-rausch-tint text-rausch text-2xl">
                        🫀
                    </span>
                    <h3 class="text-lg  text-ink">فیزیولوژی قلب و عروق</h3>
                    <p class="text-xs text-muted leading-6">
                        الکتروفیزیولوژی میوکارد، پتانسیل عمل گره SA/AV، چرخه قلبی، صداهای قلب و همودینامیک عروق محیطی.
                    </p>
                </div>
                <div class="pt-4 border-t border-hairline-soft flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۴ درس + جزوه PDF</span>
                    <a href="{{ route('catalog') }}?subject=cardiovascular-physiology" class="text-rausch font-bold hover:underline">مشاهده دوره ←</a>
                </div>
            </div>

            <!-- Card 2: Neuro -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-surface-soft text-rausch text-2xl">
                        🧠
                    </span>
                    <h3 class="text-lg  text-ink">نوروآناتومی و ناحیه بروکا</h3>
                    <p class="text-xs text-muted leading-6">
                        ساختار قشر مخ، نواحی ۴۴ و ۴۵ برودمن، دسته فیبرهای قوسی، مسیرهای حرکتی و کالبدشناسی سیستم عصبی.
                    </p>
                </div>
                <div class="pt-4 border-t border-hairline-soft flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۳ درس + اطلس رنگی</span>
                    <a href="{{ route('catalog') }}?subject=neuroanatomy" class="text-rausch font-bold hover:underline">مشاهده دوره ←</a>
                </div>
            </div>

            <!-- Card 3: Thorax & Skeleton -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-surface-soft text-ink text-2xl">
                        🩻
                    </span>
                    <h3 class="text-lg  text-ink">آناتومی بالینی قفسه سینه</h3>
                    <p class="text-xs text-muted leading-6">
                        کالبدشناسی استخوان‌بندی توراکس، عضلات بین‌دنده‌ای، دیافراگم، لندمارک‌های جراحی و عصب‌دهی محیطی.
                    </p>
                </div>
                <div class="pt-4 border-t border-hairline-soft flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۳ درس + راهنما</span>
                    <a href="{{ route('catalog') }}?subject=clinical-anatomy" class="text-rausch font-bold hover:underline">مشاهده دوره ←</a>
                </div>
            </div>

            <!-- Card 4: Cellular -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-teal/10 text-teal text-2xl">
                        🧬
                    </span>
                    <h3 class="text-lg  text-ink">فیزیولوژی سلولی و سیناپس</h3>
                    <p class="text-xs text-muted leading-6">
                        انتقال فعال و غیرفعال یونی، پمپ‌های غشایی، پتانسیل غشا و انتقال پیام در سیناپس‌های عصبی-عضلانی.
                    </p>
                </div>
                <div class="pt-4 border-t border-hairline-soft flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۲ درس + فلش‌کارت</span>
                    <a href="{{ route('catalog') }}?subject=cellular-physiology" class="text-rausch font-bold hover:underline">مشاهده دوره ←</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Learning Methodology (4 Pillars) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-b border-hairline-soft">
        <div class="text-center space-y-2 max-w-xl mx-auto">
            <span class="text-xs font-bold text-rausch">متدولوژی یادگیری فعال</span>
            <h2 class="font-display text-2xl sm:text-3xl text-ink">چهار رکن موفقیت در آزمون‌های پزشکی</h2>
            <p class="text-xs sm:text-sm text-muted">
                یادگیری در بروکا بر اساس اصول علمی تثبیت حافظه و درک مفهومی ساختاربندی شده است.
            </p>
        </div>

        <div class="mt-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="p-6 rounded-3xl bg-white/70 border border-hairline-soft space-y-3">
                <span class="text-3xl block">🎥</span>
                <h3 class="text-base  text-ink">۱. ویدیوهای بالینی و مفهومی</h3>
                <p class="text-xs text-muted leading-6">
                    ویدیوهای فشرده با تدریس اساتید و ثبت خودکار آستانه مشاهده ۷۰٪ جهت اطمینان از پیشرفت واقعی.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white/70 border border-hairline-soft space-y-3">
                <span class="text-3xl block">📄</span>
                <h3 class="text-base  text-ink">۲. جزوات و خلاصه‌های PDF</h3>
                <p class="text-xs text-muted leading-6">
                    فایل‌های خلاصه نموداری و مصور جهت دانلود در فضای امن و مرور سریع در ایام امتحانات.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white/70 border border-hairline-soft space-y-3">
                <span class="text-3xl block">🗂</span>
                <h3 class="text-base  text-ink">۳. مرور فاصله‌دار هوشمند (SM-2)</h3>
                <p class="text-xs text-muted leading-6">
                    الگوریتم بازتنظیم فواصل زمانی مرور بر اساس یادآوری شما؛ حفظ دائمی مباحث بدون فراموشی.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white/70 border border-hairline-soft space-y-3">
                <span class="text-3xl block">📝</span>
                <h3 class="text-base  text-ink">۴. آزمون‌های تشخیصی و تحلیلی</h3>
                <p class="text-xs text-muted leading-6">
                    سؤالات استاندارد چهارگزینه‌ای همراه با تحلیل تشریحی گزینه‌ها و ثبت کارنامه پیشرفت.
                </p>
            </div>
        </div>
    </section>

    <!-- Section 3: Faculty & Medical Reviewers -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-b border-hairline-soft">
        <div class="text-right space-y-2 max-w-2xl">
            <span class="text-xs font-bold text-rausch">صحت و اعتبار علمی محتوا</span>
            <h2 class="font-display text-2xl sm:text-3xl text-ink">هیئت علمی و اساتید بازبین بروکا</h2>
            <p class="text-xs sm:text-sm text-muted leading-7">
                مطابق استانداردهای YMYL، هر سرفصل توسط اساتید صاحب‌نام تدوین و توسط متخصصین مستقل بازبینی می‌شود.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Contributor 1 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-white mx-auto grid place-items-center text-xl shadow-sm">
                    👩‍⚕️
                </div>
                <h3 class="text-base  text-ink">دکتر سارا احمدی</h3>
                <p class="text-xs font-bold text-rausch">پزشک و دکترای فیزیولوژی</p>
                <p class="text-[11px] text-muted leading-5">مدرس فیزیولوژی قلب و عروق و الکتروفیزیولوژی دانشگاه علوم پزشکی.</p>
            </div>

            <!-- Contributor 2 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-white mx-auto grid place-items-center text-xl shadow-sm">
                    👨‍⚕️
                </div>
                <h3 class="text-base  text-ink">دکتر رضا کریمی</h3>
                <p class="text-xs font-bold text-rausch">متخصص بیماری‌های داخلی</p>
                <p class="text-[11px] text-muted leading-5">عضو هیئت علمی و بازبین ارشد علمی و بالینی آکادمی بروکا.</p>
            </div>

            <!-- Contributor 3 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-white mx-auto grid place-items-center text-xl shadow-sm">
                    👨‍⚕️
                </div>
                <h3 class="text-base  text-ink">دکتر نیما راد</h3>
                <p class="text-xs font-bold text-rausch">استادیار آناتومی بالینی</p>
                <p class="text-[11px] text-muted leading-5">مؤلف کتب تشریح و مدرس کورس‌های کالبدشناسی قفسه سینه و اسکلتی.</p>
            </div>

            <!-- Contributor 4 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-white mx-auto grid place-items-center text-xl shadow-sm">
                    👩‍⚕️
                </div>
                <h3 class="text-base  text-ink">دکتر مریم حسینی</h3>
                <p class="text-xs font-bold text-rausch">متخصص مغز و اعصاب</p>
                <p class="text-[11px] text-muted leading-5">پژوهشگر علوم اعصاب شناختی و مدرس کورتکس زبانی و ناحیه بروکا.</p>
            </div>
        </div>
    </section>

    <!-- Section 4: Freemium Transparency & Ready to Start CTA -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="rounded-3xl bg-ink p-8 sm:p-14 text-white text-center space-y-6 shadow-float">
            <span class="inline-flex items-center gap-2 rounded-full bg-rausch px-4 py-1 text-xs font-bold text-white">
                <span>🎓</span> شروع یادگیری بدون پیش‌نیاز مالی
            </span>
            <h2 class="font-display text-2xl sm:text-4xl max-w-2xl mx-auto leading-tight">
                همین حالا ثبت‌نام کنید و به دروس و فلش‌کارت‌های رایگان دسترسی پیدا کنید
            </h2>
            <p class="text-xs sm:text-sm text-white/70 max-w-lg mx-auto leading-7">
                در حساب رایگان، در کل آرشیو و تمام دوره‌ها، به ۲ ویدیوی منتخب، ۱ جزوه، ۱۰ فلش‌کارت مرور و ۱ نمونه‌سؤال تشخیصی به‌صورت کاملاً رایگان دسترسی دارید.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <a href="{{ route('register') }}" class="px-8 py-4 rounded-full bg-rausch text-white text-xs sm:text-sm font-bold hover:bg-rausch-active transition-colors shadow-float">
                    ساخت حساب کاربری رایگان ←
                </a>
                <a href="{{ route('plans') }}" class="px-8 py-4 rounded-full bg-white/10 border border-white/20 text-white text-xs sm:text-sm font-bold hover:bg-white/20 transition-colors">
                    مشاهده پلن‌های اشتراک
                </a>
            </div>
        </div>
    </section>
@endsection
