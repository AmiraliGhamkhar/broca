@extends('layouts.app')

@section('title', ($quiz->exists ? 'ویرایش آزمون: ' . $quiz->title : 'ساخت آزمون جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
            <div>
                <h2 class="text-2xl font-bold text-ink">{{ $quiz->exists ? 'ویرایش آزمون' : 'ساخت آزمون جدید' }}</h2>
                <p class="text-xs text-muted mt-1">مشخصات آزمون، دوره مربوطه و حدنصاب نمره قبولی</p>
            </div>
            <a href="{{ route('admin.quizzes.index') }}" class="text-xs font-bold text-rausch underline">بازگشت</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $quiz->exists ? route('admin.quizzes.update', $quiz) : route('admin.quizzes.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($quiz->exists)
                @method('patch')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-xs font-bold text-ink">عنوان آزمون</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $quiz->title) }}" required
                           placeholder="مثال: آزمون جامع فیزیولوژی قلب و گردش خون"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>

                <div>
                    <label for="course_id" class="block text-xs font-bold text-ink">دوره آموزشی</label>
                    <select id="course_id" name="course_id" required class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        <option value="">— انتخاب دوره —</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ (string)old('course_id', $quiz->course_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-ink">توضیحات و راهنمای آزمون</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="توضیح درباره مباحث مورد سنجش در این آزمون..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white">{{ old('description', $quiz->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="author_id" class="block text-xs font-bold text-ink">استاد / طراح سؤالات</label>
                    <select id="author_id" name="author_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        <option value="">— انتخاب نویسنده —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('author_id', $quiz->author_id) === (string)$c->id ? 'selected' : '' }}>
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
                            <option value="{{ $c->id }}" {{ (string)old('reviewer_id', $quiz->reviewer_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="pass_threshold_percent" class="block text-xs font-bold text-ink">حدنصاب قبولی (درصد)</label>
                    <input type="number" id="pass_threshold_percent" name="pass_threshold_percent" min="1" max="100" value="{{ old('pass_threshold_percent', $quiz->pass_threshold_percent ?? 70) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>

                <div>
                    <label for="sort_order" class="block text-xs font-bold text-ink">ترتیب نمایش</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $quiz->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $quiz->status ?? 'draft') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-hairline-soft">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-bold text-white hover:bg-rausch transition-all">
                    {{ $quiz->exists ? 'ذخیره تغییرات آزمون' : 'ثبت آزمون جدید' }}
                </button>
                <a href="{{ route('admin.quizzes.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-muted hover:bg-surface-soft">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
