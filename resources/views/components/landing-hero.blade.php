<section class="hero-shell border-b border-hairline-soft">
    <div class="section-shell relative z-10">
        <div class="grid grid-cols-1 gap-10 py-16 lg:grid-cols-12 lg:items-center lg:py-20">
            <div class="lg:col-span-7 text-right space-y-6">
                <span class="eyebrow">پلتفرم بازبینی‌شده آموزش پزشکی</span>

                <div class="space-y-4 max-w-3xl">
                    <h1 class="section-title">
                        یادگیری عمیق علوم پزشکی،
                        <span class="text-rausch">منظم</span>
                        و
                        <span class="text-rausch">قابل اعتماد</span>
                    </h1>
                    <p class="section-copy text-sm sm:text-base max-w-2xl">
                        بروکا محتوای ویدیویی، جزوات ساخت‌یافته، فلش‌کارت‌های مرور فاصله‌دار و آزمون‌های تحلیلی را در یک محیط فارسی، علمی و حرفه‌ای کنار هم می‌آورد؛ با مسیر یادگیری روشن، بازبینی علمی و دسترسی رایگان برای شروع.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <a href="{{ route('register') }}" class="button-primary">
                        <x-ui.icon name="graduation" class="size-4" />
                        شروع رایگان و ساخت حساب
                    </a>
                    <a href="{{ route('catalog') }}" class="button-secondary">
                        <x-ui.icon name="book" class="size-4" />
                        مشاهده کاتالوگ دوره‌ها
                    </a>
                </div>

                <div class="hero-evidence">
                    <span class="badge-outline">
                        <x-ui.icon name="shield" class="size-3.5" />
                        بازبینی علمی و انتشار کنترل‌شده
                    </span>
                    <span class="badge-outline">
                        <x-ui.icon name="wallet" class="size-3.5" />
                        پرداخت امن و صدور فاکتور
                    </span>
                    <span class="badge-outline">
                        <x-ui.icon name="refresh" class="size-3.5" />
                        مرور هوشمند با الگوریتم SM-2
                    </span>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="editorial-card is-dark space-y-5">
                    <div class="flex items-start justify-between gap-4 pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <span class="icon-frame-ghost">
                                <x-ui.icon name="spark" class="size-5" />
                            </span>
                            <div>
                                <p class="text-xs font-bold text-white">نمای سریع تجربه یادگیری بروکا</p>
                                <p class="mt-1 text-[11px] muted-on-dark">از آشنایی اولیه تا مرور نهایی و سنجش، همه‌چیز در یک جریان پیوسته قرار می‌گیرد.</p>
                            </div>
                        </div>
                        <span class="badge-on-dark">پروفایل آموزشی فعال</span>
                    </div>

                    <div class="trust-list">
                        <div class="trust-list-item is-dark">
                            <span class="icon-frame-ghost">
                                <x-ui.icon name="play" class="size-5" />
                            </span>
                            <div>
                                <strong class="text-white">درس‌های ویدیویی ساخت‌یافته</strong>
                                <span class="muted-on-dark">موضوعات بالینی و علوم پایه با آستانه مشاهده و پیگیری پیشرفت واقعی.</span>
                            </div>
                        </div>

                        <div class="trust-list-item is-dark">
                            <span class="icon-frame-ghost">
                                <x-ui.icon name="document" class="size-5" />
                            </span>
                            <div>
                                <strong class="text-white">جزوات و خلاصه‌های استاندارد</strong>
                                <span class="muted-on-dark">فایل‌های PDF آموزشی برای مرور سریع، مطالعه جمع‌بندی و دسترسی آفلاین.</span>
                            </div>
                        </div>

                        <div class="trust-list-item is-dark">
                            <span class="icon-frame-ghost">
                                <x-ui.icon name="stack" class="size-5" />
                            </span>
                            <div>
                                <strong class="text-white">مرور فاصله‌دار و آزمون تحلیلی</strong>
                                <span class="muted-on-dark">فلش‌کارت و آزمون در کنار هم تا یادگیری فقط مصرف محتوا نباشد، بلکه تثبیت شود.</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 pt-2">
                        <div class="rounded-2xl bg-white/5 p-3 border border-white/10">
                            <small class="text-[11px] font-bold text-white/60">فرمت محتوا</small>
                            <strong class="mt-2 block text-sm font-bold text-white">ویدیو + PDF + SRS</strong>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-3 border border-white/10">
                            <small class="text-[11px] font-bold text-white/60">مدل اعتماد</small>
                            <strong class="mt-2 block text-sm font-bold text-white">نویسنده + بازبین</strong>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-3 border border-white/10">
                            <small class="text-[11px] font-bold text-white/60">شروع اولیه</small>
                            <strong class="mt-2 block text-sm font-bold text-white">حساب رایگان</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
