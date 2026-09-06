<section class="hero-shell border-b border-hairline-soft">
    <div class="section-shell relative z-10">
        <div class="grid grid-cols-1 gap-10 py-16 lg:grid-cols-12 lg:items-center lg:py-20">
            <div class="lg:col-span-7 text-right space-y-6">
                <span class="sr-only">پلتفرم بازبینی‌شده آموزش پزشکی</span>

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
                    <a href="{{ route('register') }}" class="btn-cta-primary">
                        <x-ui.icon name="graduation" class="size-4" />
                        شروع رایگان و ساخت حساب
                    </a>
                    <a href="{{ route('catalog') }}" class="btn-cta-secondary">
                        <x-ui.icon name="book" class="size-4" />
                        مشاهده کاتالوگ دوره‌ها
                    </a>
                </div>

                <div class="hero-evidence">
                    <span class="badge-outline group/badge transition-colors duration-300 ease-out hover:border-rausch/40 hover:bg-rausch-tint/60 hover:text-rausch">
                        <x-ui.icon name="shield" class="size-3.5 transition-transform duration-300 ease-out group-hover/badge:scale-110" />
                        <span>بازبینی علمی و انتشار کنترل‌شده</span>
                    </span>
                    <span class="badge-outline group/badge transition-colors duration-300 ease-out hover:border-rausch/40 hover:bg-rausch-tint/60 hover:text-rausch">
                        <x-ui.icon name="wallet" class="size-3.5 transition-transform duration-300 ease-out group-hover/badge:scale-110" />
                        <span>تعرفهٔ شفاف و اشتراک مشخص</span>
                    </span>
                    <span class="badge-outline group/badge transition-colors duration-300 ease-out hover:border-rausch/40 hover:bg-rausch-tint/60 hover:text-rausch">
                        <x-ui.icon name="refresh" class="size-3.5 transition-transform duration-300 ease-out group-hover/badge:scale-110" />
                        <span>مرور هوشمند با الگوریتم SM-2</span>
                    </span>
                </div>
            </div>

            <div class="lg:col-span-5">
                {{-- Editorial photograph (client decision 2026-09-05: photo
                     instead of the cancelled 3D-heart video). The image IS
                     the LCP element on mobile — it is preloaded via the
                     'head' stack in welcome.blade.php, carries explicit
                     dimensions (CLS) and ships as WebP ≤ 80KB with a JPEG
                     fallback. Swap by replacing public/images/hero/hero.* and
                     updating the alt text to match the new photograph. --}}
                <figure class="relative m-0 overflow-hidden rounded-xl border border-hairline-soft bg-white shadow-float">
                    <picture>
                        <source type="image/webp" srcset="/images/hero/hero.webp">
                        <img src="/images/hero/hero.jpg"
                             alt="ماکت آموزشی قلب روی پایهٔ سفید، در نور موزه‌ای"
                             width="1200" height="1600"
                             fetchpriority="high" decoding="async"
                             class="block w-full aspect-[4/3] sm:aspect-[4/5] object-cover saturate-[.85] contrast-[.98]">
                    </picture>
                    <figcaption class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-3 bg-white/85 px-5 py-3 backdrop-blur-sm border-t border-hairline-soft">
                        <span class="sr-only">از مفهوم تا تثبیت؛ یادگیری ساخت‌یافتهٔ علوم پزشکی</span>
                        <span class="badge-outline shrink-0">
                            <x-ui.icon name="spark" class="size-3.5" />
                            <span class="sr-only">بازبینی علمی</span>
                        </span>
                    </figcaption>
                </figure>
            </div>
        </div>
    </div>
</section>
