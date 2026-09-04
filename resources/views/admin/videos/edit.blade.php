@extends('layouts.app')

@section('title', ($video->exists ? 'ویرایش ویدیو: ' . $video->title : 'ایجاد ویدیوی جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="eyebrow">فرم مدیریت ویدیو</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $video->exists ? 'ویرایش ویدیوی آموزشی' : 'افزودن ویدیوی جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">مشخصات پخش، درس مربوطه، زمان مشاهده و دسترسی رایگان را از اینجا تنظیم کنید.</p>
                </div>
                <a href="{{ route('admin.videos.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $video->exists ? route('admin.videos.update', $video) : route('admin.videos.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($video->exists)
                    @method('patch')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-xs font-bold text-ink mb-1.5">عنوان ویدیو</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $video->title) }}" required placeholder="مثال: پتانسیل عمل و پیام‌رسانی الکتریکی در میوکارد" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="course_id" class="block text-xs font-bold text-ink mb-1.5">دوره آموزشی</label>
                        <select id="course_id" name="course_id" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب دوره —</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" @selected((string) old('course_id', $video->course_id) === (string) $c->id)>{{ $c->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-ink mb-1.5">شرح درس و سرفصل ویدیو</label>
                    <textarea id="description" name="description" rows="4" placeholder="توضیح کوتاه درباره اهداف یادگیری و محتوای این ویدیو" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('description', $video->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="duration_seconds" class="block text-xs font-bold text-ink mb-1.5">مدت زمان (ثانیه)</label>
                        <input type="number" id="duration_seconds" name="duration_seconds" min="0" value="{{ old('duration_seconds', $video->duration_seconds ?? 900) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-mono" dir="ltr">
                    </div>
                    <div>
                        <label for="completion_threshold_percent" class="block text-xs font-bold text-ink mb-1.5">آستانه تکمیل (درصد)</label>
                        <input type="number" id="completion_threshold_percent" name="completion_threshold_percent" min="1" max="100" value="{{ old('completion_threshold_percent', $video->completion_threshold_percent ?? config('broca.video_completion_threshold', 70)) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                                <option value="{{ $st }}" @selected(old('status', $video->status ?? 'draft') === $st)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4">
                        <label class="flex items-start gap-3 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $video->is_free_designated) ? 'checked' : '' }} class="mt-0.5 rounded border-ink/20">
                            <span>این ویدیو به‌عنوان سهمیه پخش رایگان دوره در نظر گرفته شود.</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $video->exists ? 'ذخیره تغییرات ویدیو' : 'ثبت ویدیوی جدید' }}
                    </button>
                    <a href="{{ route('admin.videos.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چک‌لیست انتشار ویدیو</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="clock" class="size-5" /></span>
                        <div>
                            <strong>زمان معتبر</strong>
                            <span>مدت ویدیو روی گزارش پیشرفت و انتظارات کاربر اثر مستقیم دارد.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="chart" class="size-5" /></span>
                        <div>
                            <strong>آستانه تکمیل منطقی</strong>
                            <span>مقدار خیلی پایین یا خیلی بالا، کیفیت سنجش مصرف محتوا را کاهش می‌دهد.</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($video->exists)
                <div class="editorial-card is-soft">
                    <h2 class="text-sm font-extrabold text-ink">وضعیت فعلی</h2>
                    <div class="grid gap-3 mt-4 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">اسلاگ</span>
                            <span dir="ltr" class="font-bold text-ink">{{ $video->slug }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">رایگان</span>
                            <span class="{{ $video->is_free_designated ? 'badge-success' : 'badge-soft' }}">{{ $video->is_free_designated ? 'بله' : 'خیر' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
