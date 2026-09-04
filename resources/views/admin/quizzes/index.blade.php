@extends('layouts.app')

@section('title', 'مدیریت آزمون‌ها و بانک سوالات — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">آزمون و بانک سؤال</span>
            <h1 class="section-title mt-4">مدیریت آزمون‌ها و سؤالات</h1>
            <p class="section-copy mt-5">از این صفحه می‌توانید آزمون‌ها را انتخاب کنید، سؤالات هر آزمون را ببینید و چرخه طراحی، بازبینی و دسترسی رایگان را یک‌جا کنترل کنید.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="quiz" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">اصل مهم این بخش</p>
                <p class="mt-2 text-xs leading-7 text-muted">ساختار سؤال، پاسخ درست و توضیح تشریحی باید از همان ابتدا روشن باشد تا سنجش یادگیری پایدار و حرفه‌ای بماند.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mt-10">
        <a href="{{ route('admin.quizzes.create') }}" class="button-primary">
            <x-ui.icon name="quiz" class="size-4" />
            ساخت آزمون جدید
        </a>
        @if ($selectedQuiz)
            <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $selectedQuiz->id]) }}" class="button-secondary">
                <x-ui.icon name="document" class="size-4" />
                افزودن سؤال به «{{ $selectedQuiz->title }}»
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mt-8">
        <div class="space-y-4 lg:col-span-1">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold text-ink">آزمون‌ها</h2>
                <span class="badge-soft">{{ $quizzes->total() }} آزمون</span>
            </div>

            <div class="space-y-3">
                @forelse ($quizzes as $quiz)
                    <article class="editorial-card {{ ($selectedQuiz && $selectedQuiz->id === $quiz->id) ? 'ring-2 ring-rausch/25 border-rausch/20' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold text-rausch">{{ $quiz->course->title ?? '—' }}</p>
                                <h3 class="mt-2 text-sm font-black text-ink">{{ $quiz->title }}</h3>
                                <p class="mt-2 text-[11px] leading-6 text-muted">حد قبولی {{ $quiz->pass_threshold_percent ?: 70 }}٪ · {{ $quiz->questions_count }} سؤال</p>
                            </div>
                            <span class="{{ $quiz->status === 'published' ? 'badge-success' : ($quiz->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">{{ $quiz->status === 'published' ? 'منتشر شده' : ($quiz->status === 'in_review' ? 'در بازبینی' : ($quiz->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-5 pt-5 border-t border-hairline-soft text-xs">
                            <a href="{{ route('admin.quizzes.index', ['quiz_id' => $quiz->id]) }}" class="font-bold text-rausch underline">مشاهده سؤال‌ها</a>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="button-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.quizzes.destroy', $quiz) }}" onsubmit="return confirm('حذف آزمون؟');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded-full border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <h2 class="empty-state-title">هنوز آزمونی ساخته نشده است</h2>
                        <p class="empty-state-copy">اولین آزمون را ایجاد کنید تا مدیریت سؤال‌ها و سنجش یادگیری آغاز شود.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $quizzes->links() }}</div>
        </div>

        <div class="lg:col-span-2">
            @if ($selectedQuiz)
                <div class="form-panel p-7 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-hairline-soft">
                        <div>
                            <p class="text-xs font-bold text-rausch">{{ $selectedQuiz->course->title ?? '' }}</p>
                            <h2 class="text-xl font-black text-ink mt-2">سؤالات آزمون: {{ $selectedQuiz->title }}</h2>
                        </div>
                        <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $selectedQuiz->id]) }}" class="button-primary">
                            <x-ui.icon name="document" class="size-4" />
                            افزودن سؤال
                        </a>
                    </div>

                    <div class="space-y-4 mt-6">
                        @forelse ($questions as $idx => $question)
                            <article class="rounded-3xl border border-hairline-soft bg-surface-soft p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-3">
                                        <p class="text-sm font-black leading-7 text-ink"><span class="text-rausch">{{ $idx + 1 }}.</span> {{ $question->prompt }}</p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach ($question->options as $opt)
                                                <div class="rounded-2xl px-3 py-3 text-xs font-bold {{ $opt->is_correct ? 'border border-teal/25 bg-teal/10 text-teal' : 'border border-hairline-soft bg-white text-muted' }}">
                                                    {{ $opt->is_correct ? '✓' : '•' }} {{ $opt->label }}
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($question->explanation)
                                            <div class="meta-card is-soft">
                                                <p class="text-[11px] font-bold text-ink">پاسخ تشریحی</p>
                                                <p class="mt-2 text-xs leading-6 text-muted">{{ $question->explanation }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <form method="post" action="{{ route('admin.free-items.update', ['type' => 'quiz_questions', 'id' => $question->id]) }}">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="designated" value="{{ $question->is_free_designated ? 0 : 1 }}">
                                            <button type="submit" class="{{ $question->is_free_designated ? 'badge-success' : 'badge-soft' }}">
                                                {{ $question->is_free_designated ? 'نمونه رایگان ✓' : 'ویژه اشتراک' }}
                                            </button>
                                        </form>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.quizzes.questions.edit', $question) }}" class="button-soft">ویرایش</a>
                                            <form method="post" action="{{ route('admin.quizzes.questions.destroy', $question) }}" onsubmit="return confirm('حذف این سؤال؟');">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="rounded-full border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state">
                                <h2 class="empty-state-title">این آزمون هنوز سؤالی ندارد</h2>
                                <p class="empty-state-copy">اولین سؤال را با پاسخ درست، توضیح تشریحی و رفرنس علمی ثبت کنید.</p>
                                <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $selectedQuiz->id]) }}" class="button-primary mt-6">
                                    <x-ui.icon name="document" class="size-4" />
                                    ثبت اولین سؤال
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="empty-state h-full">
                    <h2 class="empty-state-title">یک آزمون را انتخاب کنید</h2>
                    <p class="empty-state-copy">برای مشاهده سؤالات و مدیریت گزینه‌ها، ابتدا یکی از آزمون‌ها را از ستون کناری انتخاب کنید.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
