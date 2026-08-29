@extends('layouts.app')

@section('title', 'مدیریت آزمون‌ها و بانک سوالات — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-ink">آزمون‌ها و بانک سؤالات چندگزینه‌ای</h2>
            <p class="text-xs text-muted mt-1">طراحی آزمون‌های تشخیصی، تعیین حدنصاب قبولی، تنظیم گزینه‌ها و پاسخ‌های تشریحی</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.quizzes.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-bold text-white hover:bg-rausch transition-all shadow-sm">
                + ساخت آزمون جدید
            </a>
            @if ($selectedQuiz)
                <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $selectedQuiz->id]) }}" class="rounded-full bg-surface-soft px-5 py-2.5 text-xs font-bold text-ink hover:bg-cream transition-all shadow-sm">
                    + افزودن سؤال به «{{ $selectedQuiz->title }}»
                </a>
            @endif
        </div>
    </div>

    <!-- 2 Column Layout: Quizzes list on Left, Questions of selected quiz on Right -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Quizzes List -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-ink flex items-center justify-between pb-2 border-b border-hairline-soft">
                <span>📝 آزمون‌های دوره‌ها</span>
                <span class="text-xs text-muted">{{ $quizzes->total() }} آزمون</span>
            </h3>

            <div class="space-y-2.5">
                @forelse ($quizzes as $quiz)
                    <div class="p-4 rounded-2xl border transition-all {{ ($selectedQuiz && $selectedQuiz->id === $quiz->id) ? 'border-rausch bg-rausch/5 shadow-sm' : 'border-hairline-soft bg-white hover:bg-white' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[11px] font-bold text-rausch">{{ $quiz->course->title ?? '—' }}</span>
                                <h4 class="text-sm font-bold text-ink mt-0.5">{{ $quiz->title }}</h4>
                                <span class="text-[11px] text-muted block mt-1">حدنصاب: {{ $quiz->pass_threshold_percent ?: 70 }}٪ · {{ $quiz->questions_count }} سؤال</span>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $quiz->status === 'published' ? 'bg-teal/10 text-teal' : 'bg-surface-soft text-ink' }}">
                                {{ $quiz->status }}
                            </span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-hairline-soft flex items-center justify-between text-xs">
                            <a href="{{ route('admin.quizzes.index', ['quiz_id' => $quiz->id]) }}" class="font-bold text-rausch underline">
                                سوالات آزمون ({{ $quiz->questions_count }}) →
                            </a>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="text-muted hover:text-ink font-bold">ویرایش</a>
                                <form method="post" action="{{ route('admin.quizzes.destroy', $quiz) }}" onsubmit="return confirm('حذف آزمون؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="text-rausch hover:underline font-bold">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted p-4 text-center">هنوز آزمونی ساخته نشده است.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $quizzes->links() }}</div>
        </div>

        <!-- Questions List of Selected Quiz -->
        <div class="lg:col-span-2">
            @if ($selectedQuiz)
                <div class="surface-panel p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-hairline-soft">
                        <div>
                            <span class="text-xs font-bold text-rausch">{{ $selectedQuiz->course->title ?? '' }}</span>
                            <h3 class="text-lg font-bold text-ink">سؤالات آزمون: {{ $selectedQuiz->title }}</h3>
                        </div>
                        <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $selectedQuiz->id]) }}"
                           class="rounded-full bg-ink px-4 py-2 text-xs font-bold text-white hover:bg-rausch transition-all">
                            + افزودن سؤال چهارگزینه‌ای
                        </a>
                    </div>

                    <div class="mt-4 space-y-4">
                        @forelse ($questions as $idx => $question)
                            <div class="p-4 rounded-2xl bg-surface-soft border border-hairline-soft">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-2 flex-1">
                                        <p class="text-xs font-bold text-ink leading-5">
                                            <span class="text-rausch">{{ $idx + 1 }}.</span> {{ $question->prompt }}
                                        </p>

                                        <!-- Options list -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                            @foreach ($question->options as $opt)
                                                <div class="p-2.5 rounded-xl text-xs font-bold flex items-center gap-2 {{ $opt->is_correct ? 'bg-teal/15 text-teal border border-teal/30' : 'bg-surface-soft text-muted' }}">
                                                    <span>{{ $opt->is_correct ? '✓' : '•' }}</span>
                                                    <span>{{ $opt->label }}</span>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if ($question->explanation)
                                            <div class="mt-2 p-2.5 rounded-xl bg-surface-soft/30 text-xs text-ink/80 leading-5">
                                                <span class="font-bold text-ink">پاسخ تشریحی:</span> {{ $question->explanation }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <form method="post" action="{{ route('admin.free-items.update', ['type' => 'quiz_questions', 'id' => $question->id]) }}">
                                            @csrf @method('patch')
                                            <input type="hidden" name="designated" value="{{ $question->is_free_designated ? 0 : 1 }}">
                                            <button type="submit" class="rounded-full px-2.5 py-0.5 text-[11px] font-bold transition-all {{ $question->is_free_designated ? 'bg-teal text-white' : 'bg-surface-soft text-muted' }}">
                                                {{ $question->is_free_designated ? 'نمونه رایگان ✓' : 'ویژه اشتراک' }}
                                            </button>
                                        </form>

                                        <div class="flex items-center gap-2 text-xs">
                                            <a href="{{ route('admin.quizzes.questions.edit', $question) }}" class="px-2.5 py-1 rounded-full border border-ink/20 font-bold hover:bg-surface-soft">ویرایش</a>
                                            <form method="post" action="{{ route('admin.quizzes.questions.destroy', $question) }}" onsubmit="return confirm('حذف این سؤال؟');">
                                                @csrf @method('delete')
                                                <button type="submit" class="text-rausch font-bold hover:underline">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <p class="text-sm font-bold text-muted">این آزمون هنوز سؤالی ندارد.</p>
                                <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $selectedQuiz->id]) }}" class="mt-3 inline-block font-bold text-rausch underline text-xs">
                                    اولین سؤال را ثبت کنید
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="surface-panel p-12 text-center text-muted">
                    <span class="text-4xl block mb-3">👈</span>
                    <h4 class="text-base font-bold text-ink">یک آزمون را از ستون راست انتخاب کنید</h4>
                    <p class="text-xs mt-1">برای مشاهده سؤالات، تنظیم گزینه‌های درست و نادرست و توضیحات تشریحی روی یکی از آزمون‌ها کلیک کنید.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
