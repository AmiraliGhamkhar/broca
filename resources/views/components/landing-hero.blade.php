{{-- High-end Airbnb-style Warm Clinical Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-b from-sun/20 via-cream to-cream pt-12 pb-20 sm:pt-16 sm:pb-28 border-b border-broca-sand">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

            <!-- Hero Left/Right in RTL (Text column) -->
            <div class="lg:col-span-7 space-y-6 text-right">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-sun/60 border border-sun text-xs font-black text-ink shadow-xs">
                    <span class="size-2 rounded-full bg-teal animate-pulse"></span>
                    <span>آکادمی آموزش پیشرفته علوم پایه و بالینی پزشکی</span>
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-ink leading-[1.25] tracking-tight">
                    آموزش عمیق پزشکی با
                    <span class="text-coral underline decoration-sun decoration-wavy decoration-2">بروکا</span>؛
                    از فیزیولوژی تا بالین
                </h1>

                <p class="text-sm sm:text-base text-ink/75 leading-8 max-w-2xl font-medium">
                    سامانه هوشمند آموزش و آماده‌سازی دانشجویان پزشکی؛ دوره‌های ویدیویی فوق‌تخصصی، جزوات خلاصه نموداری، فلش‌کارت‌های مرور فاصله‌دار (SM-2) و آزمون‌های تشخیصی با نظارت هیئت علمی.
                </p>

                <!-- Action CTAs -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="{{ route('register') }}"
                       class="px-7 py-4 rounded-full bg-ink text-cream text-xs sm:text-sm font-black hover:bg-coral transition-all shadow-md">
                        شروع رایگان و دسترسی به دروس ←
                    </a>
                    <a href="{{ route('catalog') }}"
                       class="px-6 py-4 rounded-full border border-ink/20 bg-white/70 text-ink text-xs sm:text-sm font-black hover:bg-broca-sand transition-all">
                        کاتالوگ دوره‌ها
                    </a>
                </div>

                <!-- Trust Micro-Signals -->
                <div class="grid grid-cols-3 gap-4 pt-6 border-t border-broca-sand/80 max-w-lg text-xs font-bold text-broca-slate">
                    <div class="space-y-0.5">
                        <span class="text-ink font-black text-sm block">۱۰۰٪ علمی</span>
                        <span>بازبینی دوگانه پزشکی</span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-ink font-black text-sm block">الگوریتم SM-2</span>
                        <span>تثبیت در حافظه بلندمدت</span>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-ink font-black text-sm block">دسترسی رایگان</span>
                        <span>۲ ویدیو + ۱ جزوه در هر درس</span>
                    </div>
                </div>
            </div>

            <!-- Hero Visual Card (Clinical Interactive Preview) -->
            <div class="lg:col-span-5">
                <div class="relative rounded-3xl border-2 border-ink bg-white/80 p-6 sm:p-8 shadow-xl backdrop-blur-xs space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-2xl bg-ink text-sun font-black text-sm">
                                🫀
                            </span>
                            <div>
                                <span class="text-xs font-black text-ink block">نمای اطلس یادگیری بروکا</span>
                                <span class="text-[10px] text-broca-slate">فیزیولوژی، نورولوژی و آناتومی قفسه سینه</span>
                            </div>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black bg-teal/15 text-teal">
                            آنلاین و فعال
                        </span>
                    </div>

                    <!-- Visual Module Highlights -->
                    <div class="space-y-3 text-xs">
                        <div class="p-3.5 rounded-2xl bg-ink/5 border border-ink/10 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">🎥</span>
                                <div>
                                    <span class="font-black text-ink block">الکتروفیزیولوژی و پتانسیل عمل میوکارد</span>
                                    <span class="text-[10px] text-broca-slate">درس ۱: ساختار غشای سلولی (رایگان)</span>
                                </div>
                            </div>
                            <span class="text-xs font-black text-coral">۱۵ دقیقه</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-sun/40 border border-sun/60 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">🧠</span>
                                <div>
                                    <span class="font-black text-ink block">نوروآناتومی و ناحیه بروکا (Broca 44/45)</span>
                                    <span class="text-[10px] text-broca-slate">مسیرهای گفتاری و کورتکس زبانی</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-black bg-ink text-cream px-2 py-0.5 rounded-full">ویژه</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-ink/5 border border-ink/10 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">🗂</span>
                                <div>
                                    <span class="font-black text-ink block">مرور فاصله‌دار هوشمند (Flashcards)</span>
                                    <span class="text-[10px] text-broca-slate">تکرار خودکار بر اساس بازخورد حافظه</span>
                                </div>
                            </div>
                            <span class="text-xs font-black text-teal">SM-2 Ready</span>
                        </div>
                    </div>

                    <!-- Quick entry action -->
                    <a href="{{ route('catalog') }}"
                       class="block w-full py-3.5 rounded-full bg-ink text-cream text-center text-xs font-black hover:bg-coral transition-all">
                        مشاهده سرفصل دوره‌ها و درس‌نامه‌ها ←
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>
