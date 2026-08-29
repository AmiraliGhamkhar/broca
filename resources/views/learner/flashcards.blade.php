@extends('layouts.app')

@section('title', 'فلش‌کارت‌ها — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-6xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="text-sm font-bold text-rausch">مرور فاصله‌دار</p>
    <h1 class="mt-4 font-display text-4xl sm:text-6xl">فلش‌کارت‌ها</h1>
    <p class="mt-5 max-w-2xl leading-8 text-muted">همهٔ دسته‌های کارت دوره‌هایی که در آن‌ها ثبت‌نام کرده‌ای، یک‌جا. کارت‌های سررسیدشده را هر روز مرور کن تا در حافظهٔ بلندمدت بمانند.</p>

    <div class="mt-10 flex flex-wrap items-center gap-4">
        <span class="rounded-full bg-surface-soft px-5 py-2.5 font-bold">{{ number_format($decks->count()) }} دستهٔ کارت</span>
        <span class="rounded-full px-5 py-2.5 font-bold {{ $dueToday > 0 ? 'bg-rausch text-white' : 'bg-teal/10 text-teal' }}">
            @if ($dueToday > 0)
                {{ number_format($dueToday) }} کارت آمادهٔ مرور امروز
            @else
                مرور امروز تمام شده ✓
            @endif
        </span>
    </div>

    @if ($decks->isNotEmpty())
        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($decks as $deck)
                <article class="flex flex-col rounded-2xl bg-white border border-hairline-soft p-6 shadow-float">
                    <p class="text-sm font-bold text-muted">{{ $deck->course->subject?->name }} · {{ $deck->course->title }}</p>
                    <h2 class="mt-3 text-xl font-bold">{{ $deck->title }}</h2>
                    @if ($deck->description)
                        <p class="mt-3 line-clamp-2 leading-7 text-muted">{{ $deck->description }}</p>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm font-bold">
                        <span class="rounded-full bg-surface-soft px-3 py-1">{{ number_format($deck->cards_count) }} کارت</span>
                        @if (($dueCounts[$deck->id] ?? 0) > 0)
                            <span class="rounded-full bg-rausch px-3 py-1 text-white">{{ number_format($dueCounts[$deck->id]) }} سررسیدشده</span>
                        @else
                            <span class="rounded-full bg-teal/10 px-3 py-1 text-teal">مرور نشده</span>
                        @endif
                    </div>

                    <a href="{{ route('decks.study', [$deck->course, $deck]) }}" class="mt-6 rounded-full bg-rausch px-6 py-3 text-center font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-ink hover:bg-rausch-active transition-colors">
                        شروع مرور
                    </a>
                </article>
            @endforeach
        </div>
    @elseif ($hasEnrollments)
        <div class="mt-12 rounded-2xl border border-hairline p-8">
            <h2 class="text-2xl font-bold">هنوز دستهٔ کارتی منتشر نشده است</h2>
            <p class="mt-3 leading-8 text-muted">با انتشار کارت‌های دوره‌های شما، مرور فاصله‌دار همین‌جا فعال می‌شود.</p>
        </div>
    @else
        <div class="mt-12 rounded-2xl border border-hairline p-8">
            <h2 class="text-2xl font-bold">برای شروع، در یک دوره ثبت‌نام کن</h2>
            <p class="mt-3 leading-8 text-muted">فلش‌کارت‌های هر دوره پس از ثبت‌نام برای شما فعال می‌شوند.</p>
            <a href="{{ route('catalog') }}" class="mt-6 inline-block rounded-full bg-rausch px-7 py-4 font-bold text-white hover:bg-rausch-active transition-colors">رفتن به کاتالوگ دوره‌ها</a>
        </div>
    @endif
</section>
@endsection
