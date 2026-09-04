@extends('layouts.app')

@section('title', 'فلش‌کارت‌ها — ' . __('app.name'))
@section('meta_description', 'فهرست دسته‌های فلش‌کارت شما در بروکا همراه با کارت‌های سررسیدشده برای مرور امروز.')
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack">
    <div class="section-intro max-w-3xl">
        <span class="eyebrow">مرور فاصله‌دار</span>
        <h1 class="section-title mt-4">فلش‌کارت‌های شما</h1>
        <p class="section-copy mt-5">تمام دسته‌های فلش‌کارت دوره‌هایی که در آن‌ها ثبت‌نام کرده‌اید اینجا نمایش داده می‌شود تا بتوانید مرور روزانه را بدون سردرگمی مدیریت کنید.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">دسته‌های فعال</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($decks->count()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">مرورهای امروز</p>
            <p class="mt-3 text-3xl font-black {{ $dueToday > 0 ? 'text-rausch' : 'text-teal' }}">{{ number_format($dueToday) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">روش مرور</p>
            <p class="mt-3 text-sm font-bold leading-7 text-ink">ثبت کیفیت یادآوری برای تنظیم تکرار بعدی هر کارت</p>
        </div>
    </div>

    @if ($decks->isNotEmpty())
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3 mt-12">
            @foreach ($decks as $deck)
                <article class="editorial-card flex h-full flex-col">
                    <div class="flex items-start justify-between gap-4">
                        <span class="icon-frame"><x-ui.icon name="stack" class="size-5" /></span>
                        <span class="badge-soft">{{ $deck->course->subject?->name ?: 'دوره پزشکی' }}</span>
                    </div>

                    <p class="mt-5 text-xs font-bold text-muted">{{ $deck->course->title }}</p>
                    <h2 class="mt-3 text-xl font-black text-ink leading-8">{{ $deck->title }}</h2>
                    @if ($deck->description)
                        <p class="mt-3 text-sm leading-7 text-muted line-clamp-3">{{ $deck->description }}</p>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-2 mt-6">
                        <div class="meta-card is-soft">
                            <p class="text-[11px] font-bold text-muted">تعداد کارت</p>
                            <p class="mt-1 text-lg font-black text-ink">{{ number_format($deck->cards_count) }}</p>
                        </div>
                        <div class="meta-card is-soft">
                            <p class="text-[11px] font-bold text-muted">مرور امروز</p>
                            <p class="mt-1 text-lg font-black {{ ($dueCounts[$deck->id] ?? 0) > 0 ? 'text-rausch' : 'text-teal' }}">{{ number_format($dueCounts[$deck->id] ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-bold">
                        <span class="badge-soft">{{ $deck->author?->name ?: 'تیم آموزشی بروکا' }}</span>
                        <span class="badge-soft">{{ $deck->reviewer?->name ?: 'بازبینی در حال ثبت' }}</span>
                    </div>

                    <div class="mt-6 pt-6 border-t border-hairline-soft flex items-center justify-between gap-3">
                        <p class="text-xs leading-6 text-muted">{{ ($dueCounts[$deck->id] ?? 0) > 0 ? 'کارت‌های سررسیدشده آماده شروع هستند.' : 'در حال حاضر مروری برای امروز سررسید نشده است.' }}</p>
                        <a href="{{ route('decks.study', [$deck->course, $deck]) }}" class="button-primary shrink-0">
                            <x-ui.icon name="play" class="size-4" />
                            شروع مرور
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif ($hasEnrollments)
        <div class="empty-state mt-12">
            <h2 class="empty-state-title">هنوز دسته فلش‌کارتی منتشر نشده است</h2>
            <p class="empty-state-copy">به‌محض انتشار فلش‌کارت‌های دوره‌های شما، مرور فاصله‌دار در همین صفحه فعال خواهد شد.</p>
        </div>
    @else
        <div class="empty-state mt-12">
            <h2 class="empty-state-title">برای شروع، در یک دوره ثبت‌نام کنید</h2>
            <p class="empty-state-copy">دسته‌های فلش‌کارت هر دوره پس از ثبت‌نام برای شما فعال می‌شوند و در اینجا به‌صورت یکپارچه نمایش داده خواهند شد.</p>
            <a href="{{ route('catalog') }}" class="button-primary mt-6">
                <x-ui.icon name="graduation" class="size-4" />
                رفتن به کاتالوگ دوره‌ها
            </a>
        </div>
    @endif
</section>
@endsection
