@extends('layouts.app')

@section('title', ($question->exists ? 'ویرایش سؤال آزمون' : 'افزودن سؤال چهارگزینه‌ای') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(300px,0.7fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="eyebrow">{{ $quiz->title }} · {{ $quiz->course->title ?? '' }}</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $question->exists ? 'ویرایش سؤال آزمون' : 'افزودن سؤال چهارگزینه‌ای جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">صورت سؤال، گزینه صحیح، توضیح تشریحی و اطلاعات منبع اینجا تکمیل می‌شود.</p>
                </div>
                <a href="{{ route('admin.quizzes.index', ['quiz_id' => $quiz->id]) }}" class="button-secondary">بازگشت به سؤالات</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $question->exists ? route('admin.quizzes.questions.update', $question) : route('admin.quizzes.questions.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($question->exists)
                    @method('patch')
                @else
                    <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">
                @endif

                <div>
                    <label for="prompt" class="block text-xs font-bold text-ink mb-1.5">صورت سؤال</label>
                    <textarea id="prompt" name="prompt" rows="4" required placeholder="مثال: کدام ساختار ضربان‌ساز طبیعی قلب است؟" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('prompt', $question->prompt) }}</textarea>
                </div>

                <div class="rounded-3xl border border-hairline-soft bg-surface-soft p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-extrabold text-ink">گزینه‌های پاسخ</h2>
                        <span class="text-[11px] font-bold text-rausch">یک گزینه صحیح الزامی است</span>
                    </div>

                    @php
                        $existingOptions = $question->options->sortBy('sort_order')->values();
                        $correctIndex = 0;
                        foreach ($existingOptions as $idx => $opt) {
                            if ($opt->is_correct) {
                                $correctIndex = $idx;
                                break;
                            }
                        }
                    @endphp

                    <div class="space-y-3 mt-5">
                        @for ($i = 0; $i < 4; $i++)
                            @php
                                $optLabel = old("options.{$i}.label", $existingOptions[$i]->label ?? '');
                                $isCorrect = (string) old('correct_index', $correctIndex) === (string) $i;
                            @endphp
                            <div class="rounded-2xl border border-hairline-soft bg-white p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <label class="inline-flex items-center gap-2 shrink-0 text-xs font-bold text-ink cursor-pointer">
                                        <input type="radio" name="correct_index" value="{{ $i }}" {{ $isCorrect ? 'checked' : '' }} required class="size-4 text-teal">
                                        <span>گزینه {{ $i + 1 }} {{ $isCorrect ? '(صحیح)' : '' }}</span>
                                    </label>
                                    <input type="text" name="options[{{ $i }}][label]" value="{{ $optLabel }}" required placeholder="متن گزینه {{ $i + 1 }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div>
                    <label for="explanation" class="block text-xs font-bold text-ink mb-1.5">پاسخ تشریحی</label>
                    <textarea id="explanation" name="explanation" rows="4" placeholder="توضیحی که پس از ثبت پاسخ به دانشجو نمایش داده می‌شود" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('explanation', $question->explanation) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="source_citation" class="block text-xs font-bold text-ink mb-1.5">منبع / رفرنس علمی</label>
                        <input type="text" id="source_citation" name="source_citation" value="{{ old('source_citation', $question->source_citation) }}" placeholder="مثال: گایتون فصل ۹" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب در آزمون</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $question->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="author_id" class="block text-xs font-bold text-ink mb-1.5">طراح سؤال</label>
                        <select id="author_id" name="author_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب طراح —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('author_id', $question->author_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reviewer_id" class="block text-xs font-bold text-ink mb-1.5">بازبین علمی</label>
                        <select id="reviewer_id" name="reviewer_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب بازبین پزشکی —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('reviewer_id', $question->reviewer_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                                <option value="{{ $st }}" @selected(old('status', $question->status ?? 'published') === $st)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4">
                        <label class="flex items-start gap-3 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $question->is_free_designated) ? 'checked' : '' }} class="mt-0.5 rounded border-ink/20">
                            <span>این سؤال به‌عنوان نمونه رایگان آزمون در نظر گرفته شود.</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $question->exists ? 'ذخیره تغییرات سؤال' : 'ثبت سؤال در آزمون' }}
                    </button>
                    <a href="{{ route('admin.quizzes.index', ['quiz_id' => $quiz->id]) }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چک‌لیست سؤال استاندارد</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="badge-check" class="size-5" /></span>
                        <div>
                            <strong>گزینه صحیح یکتا</strong>
                            <span>حتماً یک پاسخ درست مشخص کنید تا پردازش نتیجه آزمون بدون ابهام باشد.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="book" class="size-5" /></span>
                        <div>
                            <strong>توضیح و منبع علمی</strong>
                            <span>وجود پاسخ تشریحی و رفرنس، کیفیت آموزشی و قابلیت دفاع از محتوا را بالا می‌برد.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection
