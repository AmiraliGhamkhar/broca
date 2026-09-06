@extends('layouts.app')

@section('title', 'آزمون‌ها — ' . __('app.name'))
@section('meta_description', 'فهرست آزمون‌های فعال شما در بروکا همراه با تعداد تلاش‌ها، بهترین نتیجه و دسترسی سریع به سنجش یادگیری.')
@section('robots', 'noindex, follow')

@section('content')
@php($displayTimezone = config('broca.display_timezone'))
<section class="section-shell section-stack">
    <div class="section-intro max-w-3xl">
        <span class="sr-only">سنجش یادگیری</span>
        <h1 class="section-title mt-4">آزمون‌های شما</h1>
        <p class="section-copy mt-5">آزمون‌های دوره‌هایی که در آن‌ها ثبت‌نام کرده‌اید، اینجا با وضعیت تلاش‌ها، بهترین نتیجه و مسیر شروع دوباره نمایش داده می‌شوند.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">آزمون‌های فعال</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($quizzes->count()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">دوره‌های دارای دسترسی</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ $hasEnrollments ? 'فعال' : 'ندارید' }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">نوع ارزیابی</p>
            <p class="mt-3 text-sm font-bold leading-7 text-ink">بدون محدودیت زمان، با نمایش نتیجه و ثبت بهترین تلاش</p>
        </div>
    </div>

    @if ($quizzes->isNotEmpty())
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3 mt-12">
            @foreach ($quizzes as $quiz)
                @php($stats = $quizStats[$quiz->id] ?? ['tries' => 0, 'best' => null, 'last' => null])
                @php($passThreshold = $quiz->pass_threshold_percent ?: 70)
                <article class="editorial-card flex h-full flex-col">
                    <div class="flex items-start justify-between gap-4">
                        <span class="icon-frame"><x-ui.icon name="quiz" class="size-5" /></span>
                        <span class="badge-soft">{{ $quiz->course->subject?->name ?: 'دوره پزشکی' }}</span>
                    </div>

                    <p class="mt-5 text-xs font-bold text-muted">{{ $quiz->course->title }}</p>
                    <h2 class="mt-3 text-xl font-black text-ink leading-8">{{ $quiz->title }}</h2>
                    @if ($quiz->description)
                        <p class="mt-3 text-sm leading-7 text-muted line-clamp-3">{{ $quiz->description }}</p>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-2 mt-6">
                        <div class="meta-card is-soft">
                            <p class="text-[11px] font-bold text-muted">تعداد سؤال</p>
                            <p class="mt-1 text-lg font-black text-ink">{{ number_format($quiz->questions_count) }}</p>
                        </div>
                        <div class="meta-card is-soft">
                            <p class="text-[11px] font-bold text-muted">حد قبولی</p>
                            <p class="mt-1 text-lg font-black text-ink">{{ $passThreshold }}٪</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-6 text-xs font-bold">
                        @if (($stats['tries'] ?? 0) > 0)
                            <span class="{{ ($stats['best'] ?? 0) >= $passThreshold ? 'badge-success' : 'badge-soft' }}">بهترین نتیجه: {{ $stats['best'] }}٪</span>
                            <span class="badge-soft">{{ $stats['tries'] }} تلاش</span>
                        @else
                            <span class="badge-soft">بدون تلاش قبلی</span>
                        @endif
                    </div>

                    @if (($stats['last'] ?? null)?->exists)
                        <p class="mt-4 text-xs leading-6 text-muted">آخرین تلاش شما در {{ $stats['last']->submitted_at?->timezone($displayTimezone)->format('Y/m/d H:i') }}</p>
                    @endif

                    <div class="mt-6 pt-6 border-t border-hairline-soft flex items-center justify-between gap-3">
                        <div class="text-xs leading-6 text-muted">
                            نتیجه بلافاصله پس از ثبت نمایش داده می‌شود.
                        </div>
                        <a href="{{ route('quizzes.show', $quiz) }}" class="button-primary shrink-0">
                            <x-ui.icon name="play" class="size-4" />
                            @if (($stats['tries'] ?? 0) > 0)
                                تلاش دوباره
                            @else
                                شروع آزمون
                            @endif
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @elseif ($hasEnrollments)
        <div class="empty-state mt-12">
            <h2 class="empty-state-title">هنوز آزمونی منتشر نشده است</h2>
            <p class="empty-state-copy">به‌محض انتشار آزمون‌های دوره‌های شما، همین‌جا می‌توانید آن‌ها را شروع کنید و بهترین نتیجه‌تان را ثبت نمایید.</p>
        </div>
    @else
        <div class="empty-state mt-12">
            <h2 class="empty-state-title">برای شروع، در یک دوره ثبت‌نام کنید</h2>
            <p class="empty-state-copy">پس از ثبت‌نام در دوره‌ها، آزمون‌های هر درس و مرورهای سنجشی شما از همین صفحه در دسترس خواهند بود.</p>
            <a href="{{ route('catalog') }}" class="button-primary mt-6">
                <x-ui.icon name="graduation" class="size-4" />
                رفتن به کاتالوگ دوره‌ها
            </a>
        </div>
    @endif
</section>
@endsection
