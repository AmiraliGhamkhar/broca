@extends('layouts.app')

@section('title', ($video->exists ? 'ویرایش ویدیو: ' . $video->title : 'ایجاد ویدیوی جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
            <div>
                <h2 class="text-2xl font-bold text-ink">{{ $video->exists ? 'ویرایش ویدیوی آموزشی' : 'افزودن ویدیوی جدید' }}</h2>
                <p class="text-xs text-muted mt-1">مشخصات پخش، دوره مربوطه، زمان و وضعیت دسترسی</p>
            </div>
            <a href="{{ route('admin.videos.index') }}" class="text-xs font-bold text-rausch underline">بازگشت به لیست ویدیوها</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $video->exists ? route('admin.videos.update', $video) : route('admin.videos.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($video->exists)
                @method('patch')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-xs font-bold text-ink">عنوان ویدیو</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $video->title) }}" required
                           placeholder="مثال: پتانسیل عمل و پیام‌رسانی الکتریکی در میوکارد"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>

                <div>
                    <label for="course_id" class="block text-xs font-bold text-ink">دوره آموزشی</label>
                    <select id="course_id" name="course_id" required class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        <option value="">— انتخاب دوره —</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ (string)old('course_id', $video->course_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-ink">شرح درس و سرفصل ویدیو</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="توضیحات تکمیلی ویدیو..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white">{{ old('description', $video->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="duration_seconds" class="block text-xs font-bold text-ink">مدت زمان (به ثانیه)</label>
                    <input type="number" id="duration_seconds" name="duration_seconds" min="0" value="{{ old('duration_seconds', $video->duration_seconds ?? 900) }}"
                           placeholder="مثلاً ۹۰۰ ثانیه = ۱۵ دقیقه"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-mono bg-white">
                </div>

                <div>
                    <label for="completion_threshold_percent" class="block text-xs font-bold text-ink">آستانه تکمیل (درصد)</label>
                    <input type="number" id="completion_threshold_percent" name="completion_threshold_percent" min="1" max="100" value="{{ old('completion_threshold_percent', $video->completion_threshold_percent ?? config('broca.video_completion_threshold', 70)) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-xs font-bold text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $video->status ?? 'draft') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $video->is_free_designated) ? 'checked' : '' }} class="rounded border-ink/20">
                        <span class="text-xs font-bold text-ink">سهمیه پخش رایگان (سقف ۲ ویدیو)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-hairline-soft">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-bold text-white hover:bg-rausch transition-all">
                    {{ $video->exists ? 'ذخیره تغییرات ویدیو' : 'ثبت ویدیوی جدید' }}
                </button>
                <a href="{{ route('admin.videos.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-muted hover:bg-surface-soft">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
