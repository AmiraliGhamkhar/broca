@extends('layouts.app')

@section('title', ($quiz->exists ? 'ویرایش آزمون: ' . $quiz->title : 'ساخت آزمون جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(300px,0.7fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="eyebrow">فرم مدیریت آزمون</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $quiz->exists ? 'ویرایش آزمون' : 'ساخت آزمون جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">دوره مربوطه، توضیحات، صاحب محتوا، بازبین علمی و حد قبولی را از همین فرم تنظیم کنید.</p>
                </div>
                <a href="{{ route('admin.quizzes.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $quiz->exists ? route('admin.quizzes.update', $quiz) : route('admin.quizzes.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($quiz->exists)
                    @method('patch')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-xs font-bold text-ink mb-1.5">عنوان آزمون</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $quiz->title) }}" required placeholder="مثال: آزمون جامع فیزیولوژی قلب" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="course_id" class="block text-xs font-bold text-ink mb-1.5">دوره آموزشی</label>
                        <select id="course_id" name="course_id" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب دوره —</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" @selected((string) old('course_id', $quiz->course_id) === (string) $c->id)>{{ $c->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-ink mb-1.5">توضیحات و راهنمای آزمون</label>
                    <textarea id="description" name="description" rows="4" placeholder="مباحث مورد سنجش و نحوه استفاده از آزمون را توضیح دهید" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('description', $quiz->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="author_id" class="block text-xs font-bold text-ink mb-1.5">طراح آزمون</label>
                        <select id="author_id" name="author_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب نویسنده —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('author_id', $quiz->author_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reviewer_id" class="block text-xs font-bold text-ink mb-1.5">بازبین علمی</label>
                        <select id="reviewer_id" name="reviewer_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب بازبین پزشکی —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('reviewer_id', $quiz->reviewer_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="pass_threshold_percent" class="block text-xs font-bold text-ink mb-1.5">حد قبولی (درصد)</label>
                        <input type="number" id="pass_threshold_percent" name="pass_threshold_percent" min="1" max="100" value="{{ old('pass_threshold_percent', $quiz->pass_threshold_percent ?? 70) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب نمایش</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $quiz->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                                <option value="{{ $st }}" @selected(old('status', $quiz->status ?? 'draft') === $st)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $quiz->exists ? 'ذخیره تغییرات آزمون' : 'ثبت آزمون جدید' }}
                    </button>
                    <a href="{{ route('admin.quizzes.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چک‌لیست کیفیت آزمون</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <strong>طراح و بازبین مشخص</strong>
                            <span>این اطلاعات در تجربه کاربر و متادیتای محتوای آموزشی نقش اعتمادساز دارند.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="chart" class="size-5" /></span>
                        <div>
                            <strong>حد قبولی معنادار</strong>
                            <span>حدنصاب باید با هدف آموزشی و سختی سؤال‌ها هم‌خوانی داشته باشد.</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($quiz->exists)
                <div class="editorial-card is-soft">
                    <h2 class="text-sm font-extrabold text-ink">اقدام سریع</h2>
                    <a href="{{ route('admin.quizzes.questions.create', ['quiz_id' => $quiz->id]) }}" class="button-secondary mt-4 w-full justify-center">افزودن سؤال به این آزمون</a>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
