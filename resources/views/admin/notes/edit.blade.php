@extends('layouts.app')

@section('title', ($note->exists ? 'ویرایش جزوه: ' . $note->title : 'ایجاد جزوه جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="sr-only">فرم مدیریت جزوه</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $note->exists ? 'ویرایش جزوه' : 'افزودن جزوه جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">فایل خصوصی، توضیحات، انتساب به دوره و وضعیت دسترسی جزوه را از اینجا به‌روز کنید.</p>
                </div>
                <a href="{{ route('admin.notes.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch-text" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $note->exists ? route('admin.notes.update', $note) : route('admin.notes.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($note->exists)
                    @method('patch')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-xs font-bold text-ink mb-1.5">عنوان جزوه</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $note->title) }}" required placeholder="مثال: خلاصه نموداری الکتروفیزیولوژی قلب" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="course_id" class="block text-xs font-bold text-ink mb-1.5">دوره آموزشی</label>
                        <select id="course_id" name="course_id" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب دوره —</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" @selected((string) old('course_id', $note->course_id) === (string) $c->id)>{{ $c->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-ink mb-1.5">توضیحات و محتوای جزوه</label>
                    <textarea id="description" name="description" rows="4" placeholder="توضیح کوتاه درباره محتوای جزوه و کاربرد آن در مسیر یادگیری" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('description', $note->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="storage_key" class="block text-xs font-bold text-ink mb-1.5">کلید ذخیره‌سازی خصوصی</label>
                        <input type="text" id="storage_key" name="storage_key" value="{{ old('storage_key', $note->storage_key) }}" placeholder="notes/sample-note.pdf" dir="ltr" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-mono">
                    </div>
                    <div>
                        <label for="mime_type" class="block text-xs font-bold text-ink mb-1.5">نوع فایل (MIME)</label>
                        <input type="text" id="mime_type" name="mime_type" value="{{ old('mime_type', $note->mime_type ?? 'application/pdf') }}" dir="ltr" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="author_id" class="block text-xs font-bold text-ink mb-1.5">نویسنده علمی</label>
                        <select id="author_id" name="author_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب نویسنده —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('author_id', $note->author_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reviewer_id" class="block text-xs font-bold text-ink mb-1.5">بازبین علمی</label>
                        <select id="reviewer_id" name="reviewer_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            <option value="">— انتخاب بازبین پزشکی —</option>
                            @foreach ($contributors as $c)
                                <option value="{{ $c->id }}" @selected((string) old('reviewer_id', $note->reviewer_id) === (string) $c->id)>{{ $c->name }} ({{ $c->credentials }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب نمایش</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $note->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                                <option value="{{ $st }}" @selected(old('status', $note->status ?? 'draft') === $st)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4">
                        <label class="flex items-start gap-3 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $note->is_free_designated) ? 'checked' : '' }} class="mt-0.5 rounded border-ink/20">
                            <span>این جزوه به‌عنوان سهمیه رایگان دوره در نظر گرفته شود.</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $note->exists ? 'ذخیره تغییرات جزوه' : 'ثبت جزوه جدید' }}
                    </button>
                    <a href="{{ route('admin.notes.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چک‌لیست فایل خصوصی</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>مسیر ذخیره معتبر</strong>
                            <span>کلید ذخیره‌سازی باید به فایل خصوصی درست اشاره کند تا دانلود عمومی نشود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <strong>نویسنده و بازبین</strong>
                            <span>نمایش این داده‌ها در تجربه کاربر به اعتمادپذیری محتوای پزشکی کمک می‌کند.</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($note->exists)
                <div class="editorial-card is-soft">
                    <h2 class="text-sm font-extrabold text-ink">وضعیت فعلی</h2>
                    <div class="grid gap-3 mt-4 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">رایگان</span>
                            <span class="{{ $note->is_free_designated ? 'badge-success' : 'badge-soft' }}">{{ $note->is_free_designated ? 'بله' : 'خیر' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">کلید ذخیره</span>
                            <span dir="ltr" class="font-mono text-[11px] text-ink">{{ $note->storage_key ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
