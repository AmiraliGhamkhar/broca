@extends('layouts.app')

@section('title', ($post->exists ? 'ویرایش مقاله: ' . $post->title : 'ایجاد مقاله جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="eyebrow">فرم مدیریت مقاله</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $post->exists ? 'ویرایش مقاله وبلاگ' : 'ایجاد مقاله جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">مقاله‌های پزشکی بهتر است ابتدا ذخیره و بازبینی شوند و سپس از مسیر انتشار رسمی منتشر گردند.</p>
                </div>
                <a href="{{ route('admin.blogs.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($post->exists)
                <div class="mt-6 flex flex-wrap items-center gap-2 text-xs">
                    <span class="badge-soft">اسلاگ: {{ $post->slug }}</span>
                    <span class="{{ $post->status === 'published' ? 'badge-success' : ($post->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">
                        وضعیت: {{ ['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'آرشیو'][$post->status] ?? $post->status }}
                    </span>
                </div>
            @endif

            <form method="post" action="{{ $post->exists ? route('admin.blogs.update', $post) : route('admin.blogs.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($post->exists)
                    @method('patch')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="title" class="block text-xs font-bold text-ink mb-1.5">عنوان مقاله</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="category" class="block text-xs font-bold text-ink mb-1.5">دسته‌بندی</label>
                        <input type="text" id="category" name="category" value="{{ old('category', $post->category) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="author_name" class="block text-xs font-bold text-ink mb-1.5">نام نویسنده</label>
                        <input type="text" id="author_name" name="author_name" value="{{ old('author_name', $post->author_name) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="reviewer_name" class="block text-xs font-bold text-ink mb-1.5">نام بازبین علمی</label>
                        <input type="text" id="reviewer_name" name="reviewer_name" value="{{ old('reviewer_name', $post->reviewer_name) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                </div>

                <div>
                    <label for="excerpt" class="block text-xs font-bold text-ink mb-1.5">خلاصه کوتاه</label>
                    <textarea id="excerpt" name="excerpt" rows="3" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>

                <div>
                    <label for="content" class="block text-xs font-bold text-ink mb-1.5">متن کامل مقاله</label>
                    <textarea id="content" name="content" rows="14" required class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('content', $post->content) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="meta_title" class="block text-xs font-bold text-ink mb-1.5">Meta title</label>
                        <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label for="meta_description" class="block text-xs font-bold text-ink mb-1.5">Meta description</label>
                        <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description', $post->meta_description) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cover_image_path" class="block text-xs font-bold text-ink mb-1.5">آدرس تصویر شاخص</label>
                        <input type="text" id="cover_image_path" name="cover_image_path" value="{{ old('cover_image_path', $post->cover_image_path) }}" placeholder="/storage/blog-covers/... یا https://..." class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm" dir="ltr">
                    </div>
                    <div>
                        <label for="published_at" class="block text-xs font-bold text-ink mb-1.5">زمان انتشار (اختیاری)</label>
                        <input type="text" id="published_at" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d H:i:s')) }}" placeholder="2026-09-04 12:00:00" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm" dir="ltr">
                    </div>
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                    @php($statusOptions = ['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'archived' => 'آرشیو'])
                    @if ($post->status === 'published')
                        @php($statusOptions = ['published' => 'منتشر شده'] + $statusOptions)
                    @endif
                    <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $post->status ?: 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($post->cover_image_path)
                    <div class="meta-card is-soft">
                        <p class="text-xs font-bold text-ink">پیش‌نمایش تصویر شاخص</p>
                        <img src="{{ $post->cover_image_path }}" alt="{{ $post->title }}" width="600" height="216" loading="lazy" decoding="async" class="mt-3 h-36 rounded-2xl border border-hairline-soft object-cover">
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $post->exists ? 'ذخیره تغییرات مقاله' : 'ثبت مقاله جدید' }}
                    </button>
                    <a href="{{ route('admin.blogs.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چرخه انتشار ایمن</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>پیش‌نویس ← بازبینی ← انتشار</strong>
                            <span>برای مقالات پزشکی بهتر است از انتشار مستقیم بدون طی مسیر بازبینی خودداری شود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <strong>نویسنده و بازبین مشخص</strong>
                            <span>این داده‌ها در صفحه عمومی مقاله و متادیتا برای مخاطب نمایش داده می‌شوند.</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($post->exists)
                <div class="editorial-card is-soft space-y-3">
                    <h2 class="text-sm font-extrabold text-ink">اقدام‌های سریع انتشار</h2>
                    @if ($post->status === 'draft')
                        <form method="post" action="{{ route('admin.blogs.transition', $post) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="status" value="in_review">
                            <button type="submit" class="button-secondary w-full justify-center">ارسال به بازبینی</button>
                        </form>
                    @elseif ($post->status === 'in_review')
                        <form method="post" action="{{ route('admin.blogs.transition', $post) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="status" value="published">
                            <button type="submit" class="button-primary w-full justify-center">
                                <x-ui.icon name="badge-check" class="size-4" />
                                انتشار مقاله
                            </button>
                        </form>
                    @elseif ($post->status === 'published')
                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="button-secondary w-full justify-center">مشاهده صفحه عمومی</a>
                    @endif
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
