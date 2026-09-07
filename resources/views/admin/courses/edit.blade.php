@extends('layouts.app')

@section('title', ($course->exists ? 'ویرایش دوره: ' . $course->title : 'ایجاد دوره جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="sr-only">فرم مدیریت دوره</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $course->exists ? 'ویرایش دوره آموزشی' : 'ایجاد دوره آموزشی جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">اطلاعات هویتی دوره، توضیح عمومی، مدرس، بازبین و وضعیت انتشار را در این فرم مدیریت کنید.</p>
                </div>
                <a href="{{ route('admin.courses.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch-text" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $course->exists ? route('admin.courses.update', $course) : route('admin.courses.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($course->exists)
                    @method('patch')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-xs font-bold text-ink mb-1.5">عنوان دوره</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required placeholder="مثال: مبانی الکتروفیزیولوژی قلب" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="subject_id" class="block text-xs font-bold text-ink mb-1.5">مبحث</label>
                        <select id="subject_id" name="subject_id" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب مبحث —</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((string) old('subject_id', $course->subject_id) === (string) $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="author_id" class="block text-xs font-bold text-ink mb-1.5">مدرس / نویسنده علمی</label>
                        <select id="author_id" name="author_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب نویسنده —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('author_id', $course->author_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reviewer_id" class="block text-xs font-bold text-ink mb-1.5">بازبین علمی</label>
                        <select id="reviewer_id" name="reviewer_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب بازبین پزشکی —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('reviewer_id', $course->reviewer_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="excerpt" class="block text-xs font-bold text-ink mb-1.5">چکیده کوتاه</label>
                    <input type="text" id="excerpt" name="excerpt" value="{{ old('excerpt', $course->excerpt) }}" placeholder="یک توضیح کوتاه برای کارت‌ها و پیش‌نمایش کاتالوگ" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-ink mb-1.5">شرح و سرفصل‌های دوره</label>
                    <textarea id="description" name="description" rows="6" placeholder="اهداف آموزشی، مباحث بالینی، سرفصل‌ها و انتظارات یادگیری" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('description', $course->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="level" class="block text-xs font-bold text-ink mb-1.5">سطح علمی</label>
                        <input type="text" id="level" name="level" value="{{ old('level', $course->level ?? 'مقدماتی تا پیشرفته') }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب نمایش</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $course->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                                <option value="{{ $st }}" @selected(old('status', $course->status ?? 'draft') === $st)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $course->exists ? 'ذخیره تغییرات دوره' : 'ثبت دوره جدید' }}
                    </button>
                    <a href="{{ route('admin.courses.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چک‌لیست شناسنامه دوره</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <strong>نویسنده و بازبین مجزا</strong>
                            <span>برای افزایش اعتبار، مدرس و بازبین علمی بهتر است نقش‌های مشخص و جداگانه داشته باشند.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="document" class="size-5" /></span>
                        <div>
                            <strong>چکیده عمومی</strong>
                            <span>این متن در کارت‌های کاتالوگ، متا توضیحات و مسیر تصمیم‌گیری کاربر اثر مستقیم دارد.</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($course->exists)
                <div class="editorial-card is-soft">
                    <h2 class="text-sm font-extrabold text-ink">وضعیت فعلی دوره</h2>
                    <div class="grid gap-3 mt-4 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">اسلاگ</span>
                            <span dir="ltr" class="font-bold text-ink">{{ $course->slug }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">وضعیت</span>
                            <span class="{{ $course->status === 'published' ? 'badge-success' : ($course->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">{{ $course->status === 'published' ? 'منتشر شده' : ($course->status === 'in_review' ? 'در بازبینی' : ($course->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
