@extends('layouts.app')

@section('title', ($question->exists ? 'ویرایش سؤال آزمون' : 'افزودن سؤال چهارگزینه‌ای') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
            <div>
                <span class="text-xs font-bold text-rausch">{{ $quiz->title }} ({{ $quiz->course->title ?? '' }})</span>
                <h2 class="text-2xl font-bold text-ink mt-0.5">{{ $question->exists ? 'ویرایش سؤال آزمون' : 'افزودن سؤال چهارگزینه‌ای جدید' }}</h2>
            </div>
            <a href="{{ route('admin.quizzes.index', ['quiz_id' => $quiz->id]) }}" class="text-xs font-bold text-rausch underline">بازگشت به سؤالات آزمون</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
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
                <label for="prompt" class="block text-xs font-bold text-ink">صورت سؤال (متن پرسش بالینی یا فیزیولوژی)</label>
                <textarea id="prompt" name="prompt" rows="3" required
                          placeholder="مثال: کدامیک از ساختارهای زیر به عنوان ضربان‌ساز طبیعی قلب عمل می‌کند؟"
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">{{ old('prompt', $question->prompt) }}</textarea>
            </div>

            <!-- Options & Correct Answer Selection -->
            <div class="p-5 rounded-2xl bg-surface-soft border border-hairline-soft space-y-4">
                <h3 class="text-xs font-bold text-ink flex items-center justify-between">
                    <span>گزینه‌های پاسخ (گزینه صحیح را با دکمه رادیویی مشخص کنید)</span>
                    <span class="text-[11px] text-rausch font-bold">* گزینه درست الزامی است</span>
                </h3>

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

                <div class="space-y-3">
                    @for ($i = 0; $i < 4; $i++)
                        @php
                            $optLabel = old("options.{$i}.label", $existingOptions[$i]->label ?? '');
                            $isCorrect = (string)old('correct_index', $correctIndex) === (string)$i;
                        @endphp
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1.5 cursor-pointer shrink-0">
                                <input type="radio" name="correct_index" value="{{ $i }}" {{ $isCorrect ? 'checked' : '' }} required class="size-4 text-teal">
                                <span class="text-xs font-bold text-ink">گزینه {{ $i + 1 }} {{ $isCorrect ? '(صحیح)' : '' }}</span>
                            </label>
                            <input type="text" name="options[{{ $i }}][label]" value="{{ $optLabel }}" required
                                   placeholder="متن گزینه {{ $i + 1 }}..."
                                   class="flex-1 p-2.5 rounded-xl border border-ink/20 text-xs bg-white font-medium">
                        </div>
                    @endfor
                </div>
            </div>

            <div>
                <label for="explanation" class="block text-xs font-bold text-ink">پاسخ تشریحی و نکته آموزشی (نمایش به دانشجو پس از ارسال پاسخ)</label>
                <textarea id="explanation" name="explanation" rows="3"
                          placeholder="توضیح کامل در مورد دلیل درستی گزینه و رد سایر گزینه‌ها..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white">{{ old('explanation', $question->explanation) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="source_citation" class="block text-xs font-bold text-ink">منبع بالینی / رفرنس علمی</label>
                    <input type="text" id="source_citation" name="source_citation" value="{{ old('source_citation', $question->source_citation) }}"
                           placeholder="مثال: گایتون فصل ۹ / هاریسون"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white">
                </div>

                <div>
                    <label for="sort_order" class="block text-xs font-bold text-ink">ترتیب در آزمون</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $question->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="author_id" class="block text-xs font-bold text-ink">طراح سؤال</label>
                    <select id="author_id" name="author_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        <option value="">— انتخاب طراح —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('author_id', $question->author_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reviewer_id" class="block text-xs font-bold text-ink">بازبین علمی</label>
                    <select id="reviewer_id" name="reviewer_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        <option value="">— انتخاب بازبین پزشکی —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('reviewer_id', $question->reviewer_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-xs font-bold text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $question->status ?? 'published') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $question->is_free_designated) ? 'checked' : '' }} class="rounded border-ink/20">
                        <span class="text-xs font-bold text-ink">نمونه سؤال رایگان (سقف ۱ سؤال در کل آزمون)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-hairline-soft">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-bold text-white hover:bg-rausch transition-all">
                    {{ $question->exists ? 'ذخیره تغییرات سؤال' : 'ثبت سؤال در آزمون' }}
                </button>
                <a href="{{ route('admin.quizzes.index', ['quiz_id' => $quiz->id]) }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-muted hover:bg-surface-soft">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
