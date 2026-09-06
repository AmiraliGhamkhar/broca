@extends('layouts.app')

@section('title', 'نتیجه آزمون — ' . $quiz->title . ' — ' . __('app.name'))
@section('meta_description', 'نتیجه تلاش شما در آزمون «' . $quiz->title . '» در بروکا.')
@section('robots', 'noindex, follow')

@section('content')
@php($passThreshold = $quiz->pass_threshold_percent ?: 70)
<section class="section-shell section-stack">
    <div class="max-w-4xl mx-auto space-y-8">
        <div class="section-intro text-center max-w-3xl mx-auto">
            <span class="sr-only">نتیجه آزمون</span>
            <h1 class="section-title mt-4">{{ $quiz->title }}</h1>
            <p class="section-copy mt-5">نتیجه این تلاش بلافاصله ذخیره شده است و در صفحه آزمون‌های شما نیز به‌عنوان بخشی از سابقه یادگیری قابل مشاهده خواهد بود.</p>
        </div>

        <div class="surface-panel bg-ink text-white p-8 sm:p-10 text-center">
            <p class="text-xs font-bold text-white/70">امتیاز این تلاش</p>
            <p class="mt-4 font-display text-7xl text-white">{{ $attempt->score_percent }}٪</p>
            <p class="mt-4 text-xl font-black text-white">{{ $attempt->passed ? 'آزمون را با موفقیت پشت سر گذاشتید.' : 'این بار به حد قبولی نرسیدید؛ دوباره تمرین کنید.' }}</p>
            <p class="mt-3 text-sm leading-7 text-white/70">{{ $attempt->correct_count }} پاسخ درست از {{ $attempt->question_count }} سؤال · حد قبولی {{ $passThreshold }}٪</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="meta-card is-soft text-center">
                <p class="text-xs font-bold text-ink">پاسخ درست</p>
                <p class="mt-3 text-3xl font-black text-ink">{{ $attempt->correct_count }}</p>
            </div>
            <div class="meta-card is-soft text-center">
                <p class="text-xs font-bold text-ink">کل سؤال‌ها</p>
                <p class="mt-3 text-3xl font-black text-ink">{{ $attempt->question_count }}</p>
            </div>
            <div class="meta-card is-soft text-center">
                <p class="text-xs font-bold text-ink">وضعیت</p>
                <p class="mt-3 text-lg font-black {{ $attempt->passed ? 'text-teal' : 'text-rausch' }}">{{ $attempt->passed ? 'قبول' : 'نیازمند مرور بیشتر' }}</p>
            </div>
        </div>

        <div class="editorial-card is-soft flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-ink">مرحله بعد چیست؟</h2>
                <p class="mt-2 text-sm leading-7 text-muted">اگر نتیجه مطلوب نبود، جزوه و ویدیوی همان درس را مرور کنید و سپس با یک تلاش تازه دوباره سنجش شوید.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('quizzes.show', $quiz) }}" class="button-primary">
                    <x-ui.icon name="refresh" class="size-4" />
                    تلاش دوباره
                </a>
                <a href="{{ route('quizzes.index') }}" class="button-secondary">
                    <x-ui.icon name="quiz" class="size-4" />
                    بازگشت به فهرست آزمون‌ها
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
