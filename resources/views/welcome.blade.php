@extends('layouts.app')

@section('title', __('app.name') . ' — ' . __('app.tagline'))

@section('content')
    <!-- Hero Section -->
    <x-landing-hero />

    <!-- Section 1: Core Medical Specialties & Courses -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-b border-broca-sand">
        <div class="text-right space-y-2 max-w-2xl">
            <span class="text-xs font-black text-coral">شاخه‌های جامع آموزش پزشکی</span>
            <h2 class="text-2xl sm:text-4xl font-black text-ink">سرفصل‌های علوم پایه و آمادگی آزمون‌های بالینی</h2>
            <p class="text-xs sm:text-sm text-broca-slate leading-7">
                تمامی دروس توسط اساتید مجرب و با تمرکز بر مفاهیم کلیدی و تفکر بالینی تدوین و بازبینی شده‌اند.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1: Cardio -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-coral/10 text-coral text-2xl font-black">
                        🫀
                    </span>
                    <h3 class="text-lg font-black text-ink">فیزیولوژی قلب و عروق</h3>
                    <p class="text-xs text-broca-slate leading-6">
                        الکتروفیزیولوژی میوکارد، پتانسیل عمل گره SA/AV، چرخه قلبی، صداهای قلب و همودینامیک عروق محیطی.
                    </p>
                </div>
                <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۴ درس + جزوه PDF</span>
                    <a href="{{ route('catalog') }}?subject=cardiovascular-physiology" class="text-coral font-black hover:underline">مشاهده دوره ←</a>
                </div>
            </div>

            <!-- Card 2: Neuro -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-sun/40 text-ink text-2xl font-black">
                        🧠
                    </span>
                    <h3 class="text-lg font-black text-ink">نوروآناتومی و ناحیه بروکا</h3>
                    <p class="text-xs text-broca-slate leading-6">
                        ساختار قشر مخ، نواحی ۴۴ و ۴۵ برودمن، دسته فیبرهای قوسی، مسیرهای حرکتی و کالبدشناسی سیستم عصبی.
                    </p>
                </div>
                <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۳ درس + اطلس رنگی</span>
                    <a href="{{ route('catalog') }}?subject=neuroanatomy" class="text-coral font-black hover:underline">مشاهده دوره ←</a>
                </div>
            </div>

            <!-- Card 3: Thorax & Skeleton -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-ink/5 text-ink text-2xl font-black">
                        🩻
                    </span>
                    <h3 class="text-lg font-black text-ink">آناتومی بالینی قفسه سینه</h3>
                    <p class="text-xs text-broca-slate leading-6">
                        کالبدشناسی استخوان‌بندی توراکس، عضلات بین‌دنده‌ای، دیافراگم، لندمارک‌های جراحی و عصب‌دهی محیطی.
                    </p>
                </div>
                <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۳ درس + راهنما</span>
                    <a href="{{ route('catalog') }}?subject=clinical-anatomy" class="text-coral font-black hover:underline">مشاهده دوره ←</a>
                </div>
            </div>

            <!-- Card 4: Cellular -->
            <div class="interactive-card surface-panel p-6 flex flex-col justify-between space-y-4 rounded-3xl">
                <div class="space-y-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-teal/10 text-teal text-2xl font-black">
                        🧬
                    </span>
                    <h3 class="text-lg font-black text-ink">فیزیولوژی سلولی و سیناپس</h3>
                    <p class="text-xs text-broca-slate leading-6">
                        انتقال فعال و غیرفعال یونی، پمپ‌های غشایی، پتانسیل غشا و انتقال پیام در سیناپس‌های عصبی-عضلانی.
                    </p>
                </div>
                <div class="pt-4 border-t border-broca-sand flex items-center justify-between text-xs">
                    <span class="font-bold text-teal">۲ درس + فلش‌کارت</span>
                    <a href="{{ route('catalog') }}?subject=cellular-physiology" class="text-coral font-black hover:underline">مشاهده دوره ←</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Learning Methodology (4 Pillars) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-b border-broca-sand">
        <div class="text-center space-y-2 max-w-xl mx-auto">
            <span class="text-xs font-black text-coral">متدولوژی یادگیری فعال</span>
            <h2 class="text-2xl sm:text-4xl font-black text-ink">چهار رکن موفقیت در آزمون‌های پزشکی</h2>
            <p class="text-xs sm:text-sm text-broca-slate">
                یادگیری در بروکا بر اساس اصول علمی تثبیت حافظه و درک مفهومی ساختاربندی شده است.
            </p>
        </div>

        <div class="mt-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="p-6 rounded-3xl bg-white/70 border border-broca-sand space-y-3">
                <span class="text-3xl block">🎥</span>
                <h3 class="text-base font-black text-ink">۱. ویدیوهای بالینی و مفهومی</h3>
                <p class="text-xs text-broca-slate leading-6">
                    ویدیوهای فشرده با تدریس اساتید و ثبت خودکار آستانه مشاهده ۷۰٪ جهت اطمینان از پیشرفت واقعی.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white/70 border border-broca-sand space-y-3">
                <span class="text-3xl block">📄</span>
                <h3 class="text-base font-black text-ink">۲. جزوات و خلاصه‌های PDF</h3>
                <p class="text-xs text-broca-slate leading-6">
                    فایل‌های خلاصه نموداری و مصور جهت دانلود در فضای امن و مرور سریع در ایام امتحانات.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white/70 border border-broca-sand space-y-3">
                <span class="text-3xl block">🗂</span>
                <h3 class="text-base font-black text-ink">۳. مرور فاصله‌دار هوشمند (SM-2)</h3>
                <p class="text-xs text-broca-slate leading-6">
                    الگوریتم بازتنظیم فواصل زمانی مرور بر اساس یادآوری شما؛ حفظ دائمی مباحث بدون فراموشی.
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white/70 border border-broca-sand space-y-3">
                <span class="text-3xl block">📝</span>
                <h3 class="text-base font-black text-ink">۴. آزمون‌های تشخیصی و تحلیلی</h3>
                <p class="text-xs text-broca-slate leading-6">
                    سؤالات استاندارد چهارگزینه‌ای همراه با تحلیل تشریحی گزینه‌ها و ثبت کارنامه پیشرفت.
                </p>
            </div>
        </div>
    </section>

    <!-- Section 3: Faculty & Medical Reviewers -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 border-b border-broca-sand">
        <div class="text-right space-y-2 max-w-2xl">
            <span class="text-xs font-black text-coral">صحت و اعتبار علمی محتوا</span>
            <h2 class="text-2xl sm:text-4xl font-black text-ink">هیئت علمی و اساتید بازبین بروکا</h2>
            <p class="text-xs sm:text-sm text-broca-slate leading-7">
                مطابق استانداردهای YMYL، هر سرفصل توسط اساتید صاحب‌نام تدوین و توسط متخصصین مستقل بازبینی می‌شود.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Contributor 1 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-sun mx-auto grid place-items-center text-xl font-black shadow-sm">
                    👩‍⚕️
                </div>
                <h3 class="text-base font-black text-ink">دکتر سارا احمدی</h3>
                <p class="text-xs font-bold text-coral">پزشک و دکترای فیزیولوژی</p>
                <p class="text-[11px] text-broca-slate leading-5">مدرس فیزیولوژی قلب و عروق و الکتروفیزیولوژی دانشگاه علوم پزشکی.</p>
            </div>

            <!-- Contributor 2 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-sun mx-auto grid place-items-center text-xl font-black shadow-sm">
                    👨‍⚕️
                </div>
                <h3 class="text-base font-black text-ink">دکتر رضا کریمی</h3>
                <p class="text-xs font-bold text-coral">متخصص بیماری‌های داخلی</p>
                <p class="text-[11px] text-broca-slate leading-5">عضو هیئت علمی و بازبین ارشد علمی و بالینی آکادمی بروکا.</p>
            </div>

            <!-- Contributor 3 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-sun mx-auto grid place-items-center text-xl font-black shadow-sm">
                    👨‍⚕️
                </div>
                <h3 class="text-base font-black text-ink">دکتر نیما راد</h3>
                <p class="text-xs font-bold text-coral">استادیار آناتومی بالینی</p>
                <p class="text-[11px] text-broca-slate leading-5">مؤلف کتب تشریح و مدرس کورس‌های کالبدشناسی قفسه سینه و اسکلتی.</p>
            </div>

            <!-- Contributor 4 -->
            <div class="surface-panel p-6 text-center space-y-3 rounded-3xl">
                <div class="size-16 rounded-full bg-ink text-sun mx-auto grid place-items-center text-xl font-black shadow-sm">
                    👩‍⚕️
                </div>
                <h3 class="text-base font-black text-ink">دکتر مریم حسینی</h3>
                <p class="text-xs font-bold text-coral">متخصص مغز و اعصاب</p>
                <p class="text-[11px] text-broca-slate leading-5">پژوهشگر علوم اعصاب شناختی و مدرس کورتکس زبانی و ناحیه بروکا.</p>
            </div>
        </div>
    </section>

    <!-- Section 4: Freemium Transparency & Ready to Start CTA -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="rounded-3xl bg-gradient-to-r from-ink via-ink/95 to-ink p-8 sm:p-14 text-cream text-center space-y-6 shadow-xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-sun/20 px-4 py-1 text-xs font-black text-sun">
                <span>🎓</span> شروع یادگیری بدون پیش‌نیاز مالی
            </span>
            <h2 class="text-2xl sm:text-4xl font-black max-w-2xl mx-auto leading-tight">
                همین حالا ثبت‌نام کنید و به دروس و فلش‌کارت‌های رایگان دسترسی پیدا کنید
            </h2>
            <p class="text-xs sm:text-sm text-cream/70 max-w-lg mx-auto leading-7">
                در تمامی دوره‌ها ۲ ویدیوی اول، ۱ جزوه خلاصه، ۱۰ کارت مرور و نمونه سؤالات تشخیصی به صورت کاملاً رایگان در دسترس شماست.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <a href="{{ route('register') }}" class="px-8 py-4 rounded-full bg-sun text-ink text-xs sm:text-sm font-black hover:bg-cream transition-all shadow-md">
                    ساخت حساب کاربری رایگان ←
                </a>
                <a href="{{ route('plans') }}" class="px-8 py-4 rounded-full bg-white/10 border border-white/20 text-cream text-xs sm:text-sm font-black hover:bg-white/20 transition-all">
                    مشاهده پلن‌های اشتراک
                </a>
            </div>
        </div>
    </section>
@endsection
