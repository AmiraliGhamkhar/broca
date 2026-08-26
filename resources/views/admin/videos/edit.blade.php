@extends('layouts.app')

@section('title', ($video->exists ? 'ویرایش ویدیو' : 'ایجاد ویدیو') . ' — ' . __('app.name'))

@section('content')
<section class="max-w-2xl mx-auto px-4 py-12 sm:py-16">
    <div class="form-panel">
    <h1 class="text-2xl font-black">{{ $video->exists ? 'ویرایش ویدیو' : 'ایجاد ویدیو' }}</h1>

    @if ($errors->any())<div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-sm font-bold" role="alert">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ $video->exists ? route('admin.videos.update', $video) : route('admin.videos.store') }}" class="mt-6 space-y-4">
        @csrf @if($video->exists) @method('patch') @endif

        <div>
            <label class="block text-sm font-bold" for="course_id">دوره</label>
            <select id="course_id" name="course_id" required class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">
                <option value="">— انتخاب دوره —</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ (string) old('course_id', $video->course_id) === (string) $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-bold" for="title">عنوان</label>
            <input id="title" name="title" value="{{ old('title', $video->title) }}" required class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">
        </div>

        <div>
            <label class="block text-sm font-bold" for="description">شرح</label>
            <textarea id="description" name="description" rows="3" class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">{{ old('description', $video->description) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold" for="duration_seconds">زمان (ثانیه)</label>
                <input id="duration_seconds" name="duration_seconds" type="number" min="0" value="{{ old('duration_seconds', $video->duration_seconds) }}" class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">
            </div>
            <div>
                <label class="block text-sm font-bold" for="completion_threshold_percent">آستانهٔ تکمیل (٪)</label>
                <input id="completion_threshold_percent" name="completion_threshold_percent" type="number" min="1" max="100" value="{{ old('completion_threshold_percent', $video->completion_threshold_percent) }}" placeholder="پیش‌فرض: {{ config('broca.video_completion_threshold') }}" class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold" for="status">وضعیت</label>
                <select id="status" name="status" class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">
                    @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشرشده', 'archived' => 'بایگانی'] as $value => $label)
                        <option value="{{ $value }}" {{ $value === old('status', $video->status ?? 'draft') ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold" for="published_at">تاریخ انتشار</label>
                <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', optional($video->published_at)->format('Y-m-d\TH:i')) }}" class="w-full p-2 border border-ink/20 rounded-xl bg-transparent mt-1">
            </div>
        </div>

        <label class="flex items-center gap-2">
            <input name="is_free_designated" type="checkbox" value="1" {{ old('is_free_designated', $video->is_free_designated) ? 'checked' : '' }} class="mt-1">
            <span class="text-sm font-bold">در سهمیهٔ محتوای رایگان قرار گیرد (سقف‌ها به‌صورت خودکار بررسی می‌شود)</span>
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-full bg-broca-accent px-6 py-2.5 font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-ink">ذخیره</button>
            <a href="{{ route('admin.videos.index') }}" class="inline-block px-4 py-2 text-broca-slate underline">انصراف</a>
        </div>
    </form>
</div>
</section>
@endsection
