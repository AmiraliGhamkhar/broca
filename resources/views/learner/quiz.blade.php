@extends('layouts.app')

@section('title', $quiz->title . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit($quiz->description ?: ('آزمون «' . $quiz->title . '» از دوره «' . $quiz->course->title . '» برای سنجش یادگیری شما در بروکا.'), 155))
@section('meta_author', $quiz->reviewer?->name ?: ($quiz->author?->name ?: __('app.name')))
@section('robots', 'noindex, follow')

@section('content')
@php($displayTimezone = config('broca.display_timezone'))
@php($passThreshold = $quiz->pass_threshold_percent ?: 70)
<section class="section-shell section-stack section-stack-tight-top">
    <a href="{{ route('courses.show', $quiz->course) }}" class="button-secondary">
        <x-ui.icon name="stack" class="size-4" />
        بازگشت به صفحه دوره
    </a>

    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)] xl:items-start">
        <div class="space-y-8">
            <div class="section-intro max-w-4xl">
                <span class="eyebrow">آزمون مرور</span>
                <p class="mt-4 text-sm font-bold text-rausch">{{ $quiz->course->subject?->name }} · {{ $quiz->course->title }}</p>
                <h1 class="section-title mt-4">{{ $quiz->title }}</h1>
                @if ($quiz->description)
                    <p class="section-copy mt-5">{{ $quiz->description }}</p>
                @endif
            </div>

            @if ($questions->isEmpty())
                <div class="empty-state">
                    <h2 class="empty-state-title">این آزمون هنوز آماده نیست</h2>
                    <p class="empty-state-copy">پس از انتشار سؤال‌های بررسی‌شده و آماده‌سازی نسخه نهایی، آزمون در همین صفحه در دسترس قرار می‌گیرد.</p>
                </div>
            @else
                <form method="post" action="{{ route('quizzes.attempts.store', $quiz) }}" class="space-y-6">
                    @csrf

                    <div class="surface-panel bg-ink text-white p-6 sm:p-8">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-bold text-white/70">تعداد سؤال</p>
                                <p class="mt-2 text-2xl font-black text-white">{{ number_format($questions->count()) }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-bold text-white/70">حد قبولی</p>
                                <p class="mt-2 text-2xl font-black text-white">{{ $passThreshold }}٪</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-bold text-white/70">نمایش نتیجه</p>
                                <p class="mt-2 text-sm font-bold leading-7 text-white">بلافاصله پس از ثبت پاسخ‌ها</p>
                            </div>
                        </div>
                    </div>

                    @foreach ($questions as $question)
                        <fieldset class="form-panel p-7 sm:p-8">
                            <legend class="text-lg font-black leading-8 text-ink">{{ $loop->iteration }}. {{ $question->prompt }}</legend>
                            <div class="space-y-3 mt-6">
                                @foreach ($question->options as $option)
                                    <label class="quiz-option flex cursor-pointer items-start gap-3 rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4 text-sm leading-7 text-ink transition hover:border-rausch/25 hover:bg-rausch-tint">
                                        <input required type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="mt-1 size-4 rounded border-ink/20 text-rausch">
                                        <span>{{ $option->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-hairline-soft bg-white p-5 shadow-float">
                        <p class="text-xs leading-6 text-muted">پاسخ‌ها پس از ثبت به‌عنوان یک تلاش ذخیره می‌شوند و بهترین نتیجه شما در داشبورد باقی می‌ماند.</p>
                        <button type="submit" class="button-primary">
                            <x-ui.icon name="badge-check" class="size-4" />
                            ثبت پاسخ‌ها
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft space-y-4">
                <h2 class="text-sm font-extrabold text-ink">شناسنامه آزمون</h2>
                <dl class="grid gap-3 text-xs leading-6 text-muted">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">طراح آزمون</dt>
                        <dd class="text-left">{{ $quiz->author?->name ?: 'تیم آموزشی بروکا' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">بازبین علمی</dt>
                        <dd class="text-left">{{ $quiz->reviewer?->name ?: 'در حال ثبت توسط تیم علمی' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">انتشار</dt>
                        <dd class="text-left">{{ $quiz->published_at?->timezone($displayTimezone)?->format('Y/m/d') ?: 'منتشرشده برای دانشجویان مجاز' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">آخرین به‌روزرسانی</dt>
                        <dd class="text-left">{{ $quiz->updated_at?->timezone($displayTimezone)?->format('Y/m/d H:i') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="editorial-card is-soft">
                <h2 class="text-base font-extrabold text-ink">راهنمای سنجش</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="chart" class="size-5" /></span>
                        <div>
                            <strong>بهترین نتیجه ثبت می‌شود</strong>
                            <span>می‌توانید چند بار تلاش کنید و روند پیشرفت خود را در صفحه آزمون‌ها مشاهده نمایید.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="badge-check" class="size-5" /></span>
                        <div>
                            <strong>سؤال‌های منتشرشده و تأییدشده</strong>
                            <span>تنها سؤال‌هایی نمایش داده می‌شوند که برای انتشار آماده شده‌اند تا تجربه سنجش پایدار و قابل اعتماد باشد.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection
