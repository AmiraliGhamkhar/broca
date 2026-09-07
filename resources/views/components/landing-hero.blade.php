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
                     dimensions (CLS). Admin-manageable via the Telegram bot
                     (/set_hero); the WebP source is only linked while a
                     current twin exists (BrandAssets removes a stale one on
                     every upload so JPEG/WebP can never disagree). --}}
                @php($heroWebpUrl = \App\Support\BrandAssets::heroWebpUrl())
                <figure class="relative m-0 overflow-hidden rounded-xl border border-hairline-soft bg-white shadow-float">
                    <picture>
                        @if ($heroWebpUrl !== null)
                            <source type="image/webp" srcset="{{ $heroWebpUrl }}">
                        @endif
                        <img src="{{ \App\Support\BrandAssets::heroJpgUrl() }}"
                             alt="ماکت آموزشی قلب روی پایهٔ سفید، در نور موزه‌ای"
                             width="1200" height="1600"
                             fetchpriority="high" decoding="async"
                             class="block w-full aspect-[4/3] sm:aspect-[4/5] object-cover saturate-[.85] contrast-[.98]">
                    </picture>
                </figure>
            </div>
        </div>
    </div>
</section>
