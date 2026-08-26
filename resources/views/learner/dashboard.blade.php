@extends('layouts.app')

@section('title', 'داشبورد — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="section-label">فضای یادگیری</p>
    <h1 class="mt-4 text-5xl font-black sm:text-7xl">سلام {{ auth()->user()->name }}</h1>
    <p class="mt-5 leading-8 text-ink/65">دوره‌هایی که در آن‌ها ثبت‌نام کرده‌ای اینجا جمع می‌شوند.</p>

    <div class="mt-12 grid gap-5 md:grid-cols-3">
        <div class="metric-card is-highlight">
            <p class="text-sm font-bold">کارت‌های مرور</p>
            <p class="mt-4 text-4xl font-black">{{ number_format($dueFlashcards) }}</p>
            <p class="mt-2 text-sm font-bold">کارت سررسیدشده برای امروز</p>
        </div>
        <div class="metric-card">
            <p class="text-sm font-bold">ویدیوهای تکمیل‌شده</p>
            <p class="mt-4 text-4xl font-black">{{ number_format($completedVideos) }}</p>
            <p class="mt-2 text-sm font-bold text-ink/65">با آستانهٔ مشاهده ثبت شده</p>
        </div>
        <div class="metric-card">
            <p class="text-sm font-bold">اشتراک</p>
            <p class="mt-4 text-2xl font-black">{{ $hasSubscription ? 'فعال' : 'رایگان' }}</p>
            <p class="mt-2 text-sm font-bold text-ink/65">
                @if ($hasSubscription)
                    دسترسی کامل به همهٔ محتوا فعال است.
                @else
                    <a href="{{ route('plans') }}" class="text-coral underline">برای دسترسی کامل اشتراک فعال کن.</a>
                @endif
            </p>
        </div>
    </div>

    <div class="mt-16 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($enrollments as $enrollment)
            <a href="{{ route('courses.show', $enrollment->course) }}" class="metric-card is-highlight">
                <p class="text-sm font-bold">{{ $enrollment->course->subject->name ?? '' }}</p>
                <h2 class="mt-8 text-2xl font-black">{{ $enrollment->course->title }}</h2>
                <p class="mt-8 text-sm font-bold">ادامهٔ مسیر ←</p>
            </a>
        @empty
            <div class="rounded-[2rem] border-2 border-ink p-8">
                <h2 class="text-2xl font-black">هنوز دوره‌ای نداری</h2>
                <a href="{{ route('catalog') }}" class="mt-5 inline-block font-black text-coral underline">رفتن به کاتالوگ</a>
            </div>
        @endforelse
    </div>

    @if ($recentAttempts->isNotEmpty())
        <div class="mt-16">
            <h2 class="text-2xl font-black">آخرین آزمون‌ها</h2>
            <ul class="mt-6 space-y-3">
                @foreach ($recentAttempts as $attempt)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-ink/15 px-5 py-4">
                        <a href="{{ route('quizzes.attempts.show', [$attempt->quiz, $attempt]) }}" class="font-black hover:text-coral">{{ $attempt->quiz->title }}</a>
                        <span class="text-sm font-bold {{ $attempt->passed ? 'text-teal' : 'text-coral' }}">{{ $attempt->score_percent }}٪ — {{ $attempt->passed ? 'قبول' : 'تلاش دوباره' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
@endsection
