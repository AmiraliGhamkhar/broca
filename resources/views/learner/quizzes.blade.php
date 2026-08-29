@extends('layouts.app')

@section('title', 'آزمون‌ها — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-6xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="text-sm font-bold text-rausch">سنجش یادگیری</p>
    <h1 class="mt-4 font-display text-4xl sm:text-6xl">آزمون‌ها</h1>
    <p class="mt-5 max-w-2xl leading-8 text-muted">آزمون‌های مرور دوره‌هایی که ثبت‌نام کرده‌ای — بدون محدودیت زمان، با پاسخ تشریحی بعد از ثبت. بهترین نتیجهٔ هر آزمون ثبت می‌شود.</p>

    @if ($quizzes->isNotEmpty())
        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($quizzes as $quiz)
                @php($stats = $quizStats[$quiz->id])
                <article class="flex flex-col rounded-2xl bg-white border border-hairline-soft p-6 shadow-float">
                    <p class="text-sm font-bold text-muted">{{ $quiz->course->subject?->name }} · {{ $quiz->course->title }}</p>
                    <h2 class="mt-3 text-xl font-bold">{{ $quiz->title }}</h2>
                    @if ($quiz->description)
                        <p class="mt-3 line-clamp-2 leading-7 text-muted">{{ $quiz->description }}</p>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm font-bold">
                        <span class="rounded-full bg-surface-soft px-3 py-1">{{ number_format($quiz->questions_count) }} سؤال</span>
                        @if (($stats['tries'] ?? 0) > 0)
                            <span class="rounded-full px-3 py-1 {{ ($stats['best'] ?? 0) >= ($quiz->pass_threshold_percent ?: 70) ? 'bg-teal text-white' : 'bg-rausch text-white' }}">
                                بهترین نتیجه: {{ $stats['best'] }}٪
                            </span>
                            <span class="rounded-full bg-surface-soft px-3 py-1 text-muted">{{ $stats['tries'] }} تلاش</span>
                        @else
                            <span class="rounded-full bg-surface-soft px-3 py-1 text-muted">تازه</span>
                        @endif
                    </div>

                    <a href="{{ route('quizzes.show', $quiz) }}" class="mt-6 rounded-full bg-rausch px-6 py-3 text-center font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-ink hover:bg-rausch-active transition-colors">
                        @if (($stats['tries'] ?? 0) > 0) تلاش دوباره @else شروع آزمون @endif
                    </a>

                    @if (($stats['last'] ?? null)?->exists)
                        <p class="mt-3 text-center text-xs text-muted">آخرین تلاش: {{ $stats['last']->submitted_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    @elseif ($hasEnrollments)
        <div class="mt-12 rounded-2xl border border-hairline p-8">
            <h2 class="text-2xl font-bold">هنوز آزمونی منتشر نشده است</h2>
            <p class="mt-3 leading-8 text-muted">با انتشار آزمون‌های دوره‌های شما، آن‌ها همین‌جا فهرست می‌شوند.</p>
        </div>
    @else
        <div class="mt-12 rounded-2xl border border-hairline p-8">
            <h2 class="text-2xl font-bold">برای شروع، در یک دوره ثبت‌نام کن</h2>
            <p class="mt-3 leading-8 text-muted">آزمون‌های هر دوره پس از ثبت‌نام برای شما فعال می‌شوند.</p>
            <a href="{{ route('catalog') }}" class="mt-6 inline-block rounded-full bg-rausch px-7 py-4 font-bold text-white hover:bg-rausch-active transition-colors">رفتن به کاتالوگ دوره‌ها</a>
        </div>
    @endif
</section>
@endsection
