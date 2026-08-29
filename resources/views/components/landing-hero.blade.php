{{-- High-end Airbnb-style Hero Section (video/poster kept, restyled) --}}
<section class="hero-shell">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center min-h-[min(720px,calc(100vh-4.75rem))] py-16 lg:py-0">

            <!-- Hero Text Column -->
            <div class="lg:col-span-7 space-y-6 text-right">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-hairline-soft text-xs font-bold text-ink shadow-float">
                    <span class="size-2 rounded-full bg-rausch"></span>
                    <span>آکادمی آموزش پیشرفته علوم پایه و بالینی پزشکی</span>
                </div>

                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl text-ink leading-[1.2]">
                    آموزش عمیق پزشکی با
                    <span class="text-rausch">بروکا</span>
                </h1>

                <p class="text-sm sm:text-base text-body leading-8 max-w-2xl">
                    سامانه هوشمند آموزش و آماده‌سازی دانشجویان پزشکی؛ دوره‌های ویدیویی فوق‌تخصصی، جزوات خلاصه نموداری، فلش‌کارت‌های مرور فاصله‌دار (SM-2) و آزمون‌های تشخیصی با نظارت هیئت علمی.
                </p>

                <!-- Action CTAs -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="{{ route('register') }}"
                       class="px-7 py-4 rounded-full bg-rausch text-white text-xs sm:text-sm font-bold hover:bg-rausch-active transition-colors shadow-float">
                        شروع رایگان و دسترسی به دروس ←
                    </a>
                    <a href="{{ route('catalog') }}"
                       class="px-6 py-4 rounded-full border border-ink bg-white text-ink text-xs sm:text-sm font-bold hover:bg-surface-soft transition-colors">
                        کاتالوگ دوره‌ها
                    </a>
                </div>

                <!-- Trust Micro-Signals -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-hairline-soft max-w-lg text-xs font-bold text-muted">
                    <div class="space-y-0.5">
                        <span class="text-ink font-bold text-sm block">۱۰۰٪ علمی</span>
                        <span>بازبینی دوگانه پزشکی</span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-ink font-bold text-sm block">الگوریتم SM-2</span>
                        <span>تثبیت در حافظه بلندمدت</span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-ink font-bold text-sm block">دسترسی رایگان</span>
                        <span>۲ ویدیو + ۱ جزوه در هر درس</span>
                    </div>
                </div>
            </div>

            <!-- Hero Visual Column -->
            <div class="lg:col-span-5">
                <div class="relative rounded-[1.25rem] border border-hairline-soft bg-white p-6 sm:p-8 shadow-float space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                        <div class="flex items-center gap-3">
                            <span class="brand-mark">🫀</span>
                            <div>
                                <span class="text-xs font-bold text-ink block">نمای اطلس یادگیری بروکا</span>
                                <span class="text-[11px] text-muted">فیزیولوژی، نورولوژی و آناتومی قفسه سینه</span>
                            </div>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold bg-rausch-tint text-rausch">
                            آنلاین و فعال
                        </span>
                    </div>

                    <!-- Visual Module Highlights -->
                    <div class="space-y-3 text-xs">
                        <div class="p-3.5 rounded-xl bg-surface-soft border border-hairline-soft flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">🎥</span>
                                <div>
                                    <span class="font-bold text-ink block">الکتروفیزیولوژی و پتانسیل عمل میوکارد</span>
                                    <span class="text-[11px] text-muted">درس ۱: ساختار غشای سلولی (رایگان)</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-rausch">۱۵ دقیقه</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-surface-soft border border-hairline-soft flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">📄</span>
                                <div>
                                    <span class="font-bold text-ink block">جزوهٔ خلاصهٔ الکتروفیزیولوژی و نوار قلب</span>
                                    <span class="text-[11px] text-muted">PDF قابل دانلود</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-teal">رایگان</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-surface-soft border border-hairline-soft flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">🗂</span>
                                <div>
                                    <span class="font-bold text-ink block">مرور فاصله‌دار کارت‌های قلب</span>
                                    <span class="text-[11px] text-muted">۱۰ کارت رایگان با الگوریتم SM-2</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-rausch">آماده مرور</span>
                        </div>
                    </div>

                    <!-- Mini CTA -->
                    <a href="{{ route('register') }}"
                       class="block w-full py-3 rounded-full bg-ink text-white text-center text-xs font-bold hover:bg-rausch transition-colors">
                        ساخت حساب رایگان و شروع یادگیری
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
