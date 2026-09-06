@extends('layouts.app')

@section('title', 'مدیریت وبلاگ — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
@php($displayTimezone = config('broca.display_timezone'))
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">وبلاگ و مقاله‌های آموزشی</span>
            <h1 class="section-title mt-4">مدیریت مقالات علمی</h1>
            <p class="section-copy mt-5">از اینجا مقاله‌های وبلاگ را با چرخه پیش‌نویس، بازبینی، انتشار و آرشیو مدیریت می‌کنید و شناسنامه نویسنده، بازبین و متادیتای جستجو را تکمیل نگه می‌دارید.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="document" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">اصل انتشار مسئولانه</p>
                <p class="mt-2 text-xs leading-7 text-muted">برای محتوای پزشکی، نمایش وضعیت انتشار، نویسنده و بازبین علمی باید شفاف باشد. این صفحه همین شفافیت را در عملیات مدیریت هم حفظ می‌کند.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">کل نتایج</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($posts->total()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">منتشرشده در این فهرست</p>
            <p class="mt-3 text-3xl font-black text-teal">{{ number_format($posts->getCollection()->where('status', 'published')->count()) }}</p>
        </div>
        <div class="meta-card is-soft flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-ink">اقدام سریع</p>
                <p class="mt-2 text-sm leading-7 text-muted">مقاله جدید با متادیتای کامل ثبت کنید.</p>
            </div>
            <a href="{{ route('admin.blogs.create') }}" class="button-primary shrink-0">
                <x-ui.icon name="document" class="size-4" />
                افزودن مقاله
            </a>
        </div>
    </div>

    <div class="surface-panel mt-8 p-4 sm:p-5">
        <form method="get" action="{{ route('admin.blogs.index') }}" class="grid gap-3 md:grid-cols-[minmax(0,1.5fr)_220px_auto] md:items-end">
            <div>
                <label for="q" class="block text-[11px] font-bold text-ink mb-1.5">جستجوی عنوان مقاله</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="مثال: فیزیولوژی قلب" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs">
            </div>
            <div>
                <label for="status" class="block text-[11px] font-bold text-ink mb-1.5">وضعیت انتشار</label>
                <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'آرشیو'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="button-secondary">
                    <x-ui.icon name="chart" class="size-4" />
                    اعمال فیلتر
                </button>
                @if (request()->hasAny(['q', 'status']))
                    <a href="{{ route('admin.blogs.index') }}" class="button-soft">پاک کردن</a>
                @endif
            </div>
        </form>
    </div>

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="border-b border-hairline-soft bg-surface-soft text-right text-muted">
                <tr>
                    <th class="p-4 font-bold">مقاله</th>
                    <th class="p-4 font-bold">اسلاگ</th>
                    <th class="p-4 font-bold">شناسنامه</th>
                    <th class="p-4 font-bold">وضعیت</th>
                    <th class="p-4 font-bold">انتشار</th>
                    <th class="p-4 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($posts as $post)
                    <tr class="align-top hover:bg-white/50">
                        <td class="p-4">
                            <div class="flex items-start gap-3">
                                @if ($post->cover_image_path)
                                    <img src="{{ $post->cover_image_path }}" alt="{{ $post->title }}" class="size-16 rounded-2xl border border-hairline-soft object-cover">
                                @else
                                    <span class="icon-frame-soft icon-frame-lg icon-frame-round shrink-0"><x-ui.icon name="document" class="size-5" /></span>
                                @endif
                                <div>
                                    <p class="text-sm font-black text-ink">{{ $post->title }}</p>
                                    @if ($post->category)
                                        <span class="badge-soft mt-2">{{ $post->category }}</span>
                                    @endif
                                    @if ($post->excerpt)
                                        <p class="mt-2 text-[11px] leading-6 text-muted">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="p-4 font-mono text-[11px] text-muted" dir="ltr">{{ $post->slug }}</td>
                        <td class="p-4 text-muted leading-6">
                            <p><span class="font-bold text-ink">نویسنده:</span> {{ $post->author_name ?: '—' }}</p>
                            <p class="mt-1"><span class="font-bold text-ink">بازبین:</span> {{ $post->reviewer_name ?: '—' }}</p>
                        </td>
                        <td class="p-4">
                            <span class="{{ match($post->status) { 'published' => 'badge-success', 'in_review' => 'badge-neutral', default => 'badge-soft' } }}">
                                {{ match($post->status) { 'published' => 'منتشر شده', 'in_review' => 'در بازبینی', 'archived' => 'آرشیو', default => 'پیش‌نویس' } }}
                            </span>
                        </td>
                        <td class="p-4 text-muted">{{ $post->published_at?->timezone($displayTimezone)->format('Y/m/d H:i') ?: '—' }}</td>
                        <td class="p-4 text-left">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.blogs.edit', $post) }}" class="button-soft">ویرایش</a>
                                @if ($post->status === 'draft')
                                    <form method="post" action="{{ route('admin.blogs.transition', $post) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="status" value="in_review">
                                        <button type="submit" class="button-secondary">ارسال به بازبینی</button>
                                    </form>
                                @elseif ($post->status === 'in_review')
                                    <form method="post" action="{{ route('admin.blogs.transition', $post) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="status" value="published">
                                        <button type="submit" class="rounded bg-teal px-4 py-2 text-xs font-bold text-white transition hover:opacity-90">انتشار</button>
                                    </form>
                                @elseif ($post->status === 'published')
                                    <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="button-secondary">مشاهده</a>
                                @endif
                                <form method="post" action="{{ route('admin.blogs.destroy', $post) }}" onsubmit="return confirm('آیا از حذف این مقاله مطمئن هستید؟');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10">
                            <div class="empty-state">
                                <h2 class="empty-state-title">هنوز مقاله‌ای ثبت نشده است</h2>
                                <p class="empty-state-copy">اولین مقاله آموزشی را با عنوان، خلاصه، نویسنده، بازبین علمی و متادیتای جستجو ایجاد کنید.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $posts->links() }}</div>
</section>
@endsection
