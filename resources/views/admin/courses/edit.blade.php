@extends('layouts.app')

@section('title', ($course->exists ? 'ویرایش دوره: ' . $course->title : 'ایجاد دوره جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
            <div>
                <h2 class="text-2xl font-black text-ink">{{ $course->exists ? 'ویرایش دوره آموزشی' : 'ایجاد دوره آموزشی جدید' }}</h2>
                <p class="text-xs text-broca-slate mt-1">اطلاعات محتوایی، اساتید پزشکی، سطح علمی و وضعیت انتشار دوره</p>
            </div>
            <a href="{{ route('admin.courses.index') }}" class="text-xs font-bold text-coral underline">بازگشت به لیست دوره‌ها</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $course->exists ? route('admin.courses.update', $course) : route('admin.courses.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($course->exists)
                @method('patch')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-xs font-black text-ink">عنوان دوره (فارسی)</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required
                           placeholder="مثال: مبانی الکتروفیزیولوژی قلب"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="subject_id" class="block text-xs font-black text-ink">مبحث / درس‌نامه</label>
                    <select id="subject_id" name="subject_id" required class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب مبحث —</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ (string)old('subject_id', $course->subject_id) === (string)$subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="author_id" class="block text-xs font-black text-ink">استاد / نویسنده علمی</label>
                    <select id="author_id" name="author_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب نویسنده —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('author_id', $course->author_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reviewer_id" class="block text-xs font-black text-ink">بازبین علمی / متخصص ناظر (باید متفاوت از نویسنده باشد)</label>
                    <select id="reviewer_id" name="reviewer_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب بازبین پزشکی —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('reviewer_id', $course->reviewer_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="excerpt" class="block text-xs font-black text-ink">چکیده کوتاه (برای کارت‌ها و پیش‌نمایش کاتالوگ)</label>
                <input type="text" id="excerpt" name="excerpt" value="{{ old('excerpt', $course->excerpt) }}"
                       placeholder="توضیح مختصر ۱-۲ جمله‌ای"
                       class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
            </div>

            <div>
                <label for="description" class="block text-xs font-black text-ink">شرح و سرفصل‌های دوره</label>
                <textarea id="description" name="description" rows="5"
                          placeholder="اهداف آموزشی، مباحث بالینی و سرفصل‌های تدریس..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white/70">{{ old('description', $course->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="level" class="block text-xs font-black text-ink">سطح علمی</label>
                    <input type="text" id="level" name="level" value="{{ old('level', $course->level ?? 'مقدماتی تا پیشرفته') }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="sort_order" class="block text-xs font-black text-ink">ترتیب نمایش</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $course->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="status" class="block text-xs font-black text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $course->status ?? 'draft') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-broca-sand">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-black text-cream hover:bg-coral transition-all">
                    {{ $course->exists ? 'ذخیره تغییرات دوره' : 'ثبت دوره جدید' }}
                </button>
                <a href="{{ route('admin.courses.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-broca-slate hover:bg-broca-sand">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
