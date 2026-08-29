@extends('layouts.app')

@section('title', $slug === 'broca-area-and-aphasia' ? 'کالبدشناسی ناحیه بروکا و مقایسه بالینی آفازی‌ها — ' . __('app.name') : 'مقاله به‌زودی — ' . __('app.name'))

@section('content')
@if ($slug === 'broca-area-and-aphasia')
<article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="space-y-4 pb-8 border-b border-hairline-soft">
        <a href="{{ route('blog.index') }}" class="text-xs font-bold text-rausch hover:underline">
            ← بازگشت به وبلاگ
        </a>
        <div class="flex items-center gap-2">
            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold bg-rausch/10 text-rausch">نورولوژی و علوم اعصاب</span>
            <span class="text-xs text-muted">زمان مطالعه: ۶ دقیقه</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-bold text-ink leading-tight">
            کالبدشناسی ناحیه بروکا و مقایسه بالینی آفازی‌های حرکتی و حسی در قشر مخ
        </h1>

        <div class="flex items-center gap-3 pt-2 text-xs text-muted">
            <span class="font-bold text-ink">نویسنده: دکتر مریم حسینی (متخصص مغز و اعصاب)</span>
            <span>•</span>
            <span>بازبین علمی: دکتر رضا کریمی</span>
        </div>
    </div>

    <div class="mt-8 prose prose-slate max-w-none text-xs sm:text-sm leading-8 text-ink/80 space-y-6">
        <p class="font-bold text-ink leading-8">
            ناحیه بروکا (Broca's Area) یکی از حیاتی‌ترین مراکز قشر حرکتی مغز برای تولید کلام و گرامر زبانی است که در سال ۱۸۶۱ توسط پزشک و انسان‌شناس فرانسوی، پل بروکا، کشف شد.
        </p>

        <h2 class="text-lg font-bold text-ink pt-4 border-t border-hairline-soft">موقعیت آناتومیک و نواحی برودمن</h2>
        <p>
            این ناحیه در شکنج تحتانی لوب فرونتال (Inferior Frontal Gyrus) نیمکره غالب (در بیش از ۹۵٪ افراد راست‌دست و ۷۰٪ چپ‌دست‌ها، نیمکره چپ) واقع شده و شامل دو بخش اصلی است:
        </p>
        <ul class="list-disc pr-6 space-y-2">
            <li><strong>Pars Opercularis (ناحیه ۴۴ برودمن):</strong> در هماهنگی حرکات عضلات حنجره، زبان و لب‌ها برای تلفظ آواها نقش دارد.</li>
            <li><strong>Pars Triangularis (ناحیه ۴۵ برودمن):</strong> در سازمان‌دهی معنایی و ساختار گرامری جملات فعال است.</li>
        </ul>

        <h2 class="text-lg font-bold text-ink pt-4 border-t border-hairline-soft">تفاوت‌های بالینی آفازی بروکا و ورنیکه</h2>
        <div class="surface-panel p-5 overflow-x-auto my-4">
            <table class="w-full text-xs text-right">
                <thead class="bg-ink/5 border-b border-hairline-soft">
                    <tr>
                        <th class="p-2.5 font-bold">شاخص بالینی</th>
                        <th class="p-2.5 font-bold text-rausch">آفازی بروکا (بیانی / Motor)</th>
                        <th class="p-2.5 font-bold text-teal">آفازی ورنیکه (درکی / Sensory)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-broca-sand">
                    <tr>
                        <td class="p-2.5 font-bold">روانی کلام (Fluency)</td>
                        <td class="p-2.5">ناروان، تلگرافی، با تلاش فراوان</td>
                        <td class="p-2.5">بسیار روان، پرحرف ولی بی‌معنی</td>
                    </tr>
                    <tr>
                        <td class="p-2.5 font-bold">درک زبان (Comprehension)</td>
                        <td class="p-2.5">نسبتاً حفظ شده است</td>
                        <td class="p-2.5">به شدت مختل است</td>
                    </tr>
                    <tr>
                        <td class="p-2.5 font-bold">آگاهی از نقص (Awareness)</td>
                        <td class="p-2.5">بیمار از ناتوانی خود آگاه و مضطرب است</td>
                        <td class="p-2.5">بیمار از بی‌معنی بودن کلامش آگاه نیست</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h2 class="text-lg font-bold text-ink pt-4 border-t border-hairline-soft">جمع‌بندی و اهمیت بالینی</h2>
        <p>
            آسیب‌های ایسکمیک در قلمرو شریان مغزی میانی (MCA) شاخه فوقانی شایع‌ترین علت بروز آفازی بروکا همراه با ضعف حرکتی اندام‌های فوقانی و صورت در سمت راست بدن است.
        </p>
    </div>

    <!-- Related Course CTA Box -->
    <div class="mt-12 p-6 rounded-3xl bg-surface-soft/30 border border-sun flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-ink">علاقه‌مند به یادگیری کامل این مبحث هستید؟</h3>
            <p class="text-xs text-muted mt-1">دوره جامع نوروآناتومی و ناحیه بروکا همراه با ویدیوها و فلش‌کارت‌های اختصاصی را مشاهده کنید.</p>
        </div>
        <a href="{{ route('catalog') }}?subject=neuroanatomy" class="px-6 py-2.5 rounded-full bg-ink text-white text-xs font-bold hover:bg-rausch transition-all">
            ورود به دوره نوروآناتومی ←
        </a>
    </div>
</article>
@else
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
        <div class="surface-panel p-10 rounded-3xl text-center space-y-4">
            <span class="text-xs font-bold text-rausch">مقاله اعلام‌شده</span>
            <h1 class="font-display text-2xl sm:text-3xl text-ink">این مقاله به‌زودی منتشر می‌شود</h1>
            <p class="text-xs sm:text-sm text-muted leading-7 max-w-lg mx-auto">
                نویسندگان در حال نگارش این مطلب هستند؛ برای اطلاع از انتشار آن به وبلاگ بروکا سر بزنید.
            </p>
            <a href="{{ route('blog.index') }}" class="inline-block rounded-full bg-ink px-7 py-3 text-xs font-bold text-white hover:bg-rausch transition-colors">
                بازگشت به وبلاگ ←
            </a>
        </div>
    </section>
@endif
@endsection
