@extends('layouts.app')

@section('title', $note->title . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit($note->description ?: ('دانلود جزوه آموزشی «' . $note->title . '» از دوره «' . $course->title . '» در بروکا.'), 155))
@section('meta_author', $note->reviewer?->name ?: ($note->author?->name ?: __('app.name')))
@section('robots', 'noindex, follow')

@section('content')
@php($displayTimezone = config('broca.display_timezone'))
<section class="section-shell section-stack section-stack-tight-top">
    <a href="{{ route('courses.show', $course) }}" class="button-secondary">
        <x-ui.icon name="stack" class="size-4" />
        بازگشت به صفحه دوره
    </a>

    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)] xl:items-start">
        <div class="space-y-8">
            <div class="section-intro max-w-4xl">
                <span class="sr-only">جزوه خصوصی دوره</span>
                <p class="mt-4 text-sm font-bold text-rausch">{{ $course->subject?->name }} · {{ $course->title }}</p>
                <h1 class="section-title mt-4">{{ $note->title }}</h1>
                @if ($note->description)
                    <p class="section-copy mt-5">{{ $note->description }}</p>
                @endif
            </div>

            <div class="surface-panel bg-ink text-white p-8 sm:p-10">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="badge-on-dark">دسترسی خصوصی</span>
                    <span class="badge-on-dark">ارسال مستقیم از فضای امن</span>
                    @if ($note->mime_type)
                        <span class="badge-on-dark">{{ strtoupper(str_replace(['application/', 'image/'], '', $note->mime_type)) }}</span>
                    @endif
                </div>

                <h2 class="mt-6 text-2xl font-black text-white">نسخه قابل دانلود برای مطالعه و مرور</h2>
                <p class="mt-3 text-sm leading-7 text-white/70">فایل جزوه از مسیر عمومی ارائه نمی‌شود و پس از بررسی دسترسی، دانلود آن برای همین کاربر انجام می‌شود.</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-bold text-white/70">نویسنده محتوا</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $note->author?->name ?: 'تیم آموزشی بروکا' }}</p>
                        <p class="mt-1 text-xs text-white/60">{{ $note->author?->credentials ?: 'محتوای درسی تأییدشده برای دانشجویان ثبت‌نام‌شده' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-bold text-white/70">بازبینی علمی</p>
                        <p class="mt-2 text-sm font-bold text-white">{{ $note->reviewer?->name ?: 'در حال ثبت توسط تیم علمی' }}</p>
                        <p class="mt-1 text-xs text-white/60">{{ $note->reviewer?->credentials ?: 'جزئیات بازبین پس از تکمیل شناسنامه محتوا نمایش داده می‌شود.' }}</p>
                    </div>
                </div>

                <a href="{{ route('notes.download', $note) }}" class="button-primary mt-8">
                    <x-ui.icon name="document" class="size-4" />
                    دریافت جزوه
                </a>
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft space-y-4">
                <h2 class="text-sm font-extrabold text-ink">شناسنامه فایل</h2>
                <dl class="grid gap-3 text-xs leading-6 text-muted">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">نوع فایل</dt>
                        <dd class="text-left">{{ $note->mime_type ?: 'در حال ثبت' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">حجم فایل</dt>
                        <dd class="text-left">{{ $note->size_bytes ? number_format(round($note->size_bytes / 1024 / 1024, 1), 1) . ' MB' : 'در حال ثبت' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">تاریخ انتشار</dt>
                        <dd class="text-left">{{ $note->published_at?->timezone($displayTimezone)?->format('Y/m/d') ?: 'منتشرشده برای دانشجویان مجاز' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">آخرین به‌روزرسانی</dt>
                        <dd class="text-left">{{ $note->updated_at?->timezone($displayTimezone)?->format('Y/m/d H:i') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="editorial-card is-soft">
                <h2 class="text-base font-extrabold text-ink">راهنمای استفاده</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="book" class="size-5" /></span>
                        <div>
                            <strong>مطالعه ساختاریافته</strong>
                            <span>پیشنهاد می‌شود جزوه را کنار ویدیوی همان درس و آزمون دوره مرور کنید تا فهم شما تثبیت شود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>دسترسی محدود به اعضای مجاز</strong>
                            <span>برای حفاظت از محتوای آموزشی، فایل از مسیر عمومی در دسترس نیست و بعد از احراز دسترسی ارسال می‌شود.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection
