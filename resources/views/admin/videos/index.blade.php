@extends('layouts.app')

@section('title', 'مدیریت ویدیوهای آموزشی — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">ویدیو، استریم و دسترسی</span>
            <h1 class="section-title mt-4">ویدیوهای آموزشی دوره‌ها</h1>
            <p class="section-copy mt-5">از اینجا ویدیوها، زمان، آستانه تکمیل، سهمیه رایگان و وضعیت انتشار هر درس را کنترل می‌کنید.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="play" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">استاندارد انتشار ویدیو</p>
                <p class="mt-2 text-xs leading-7 text-muted">عنوان واضح، انتساب به دوره، زمان معتبر و آستانه تکمیل مناسب کمک می‌کند تجربه یادگیری و گزارش پیشرفت قابل اتکا باقی بماند.</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">نتایج فهرست</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($videos->total()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">منتشرشده</p>
            <p class="mt-3 text-3xl font-black text-teal">{{ number_format($videos->getCollection()->where('status', 'published')->count()) }}</p>
        </div>
        <div class="meta-card is-soft flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-ink">اقدام سریع</p>
                <p class="mt-2 text-sm leading-7 text-muted">یک ویدیوی تازه به دوره‌های آموزشی اضافه کنید.</p>
            </div>
            <a href="{{ route('admin.videos.create') }}" class="button-primary shrink-0">
                <x-ui.icon name="play" class="size-4" />
                افزودن ویدیو
            </a>
        </div>
    </div>

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="border-b border-hairline-soft bg-surface-soft text-right text-muted">
                <tr>
                    <th class="p-4 font-bold">ویدیو</th>
                    <th class="p-4 font-bold">دوره</th>
                    <th class="p-4 font-bold text-center">مدت</th>
                    <th class="p-4 font-bold text-center">آستانه تکمیل</th>
                    <th class="p-4 font-bold text-center">دسترسی رایگان</th>
                    <th class="p-4 font-bold">وضعیت</th>
                    <th class="p-4 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($videos as $video)
                    <tr class="align-top hover:bg-white/50">
                        <td class="p-4">
                            <div class="flex items-start gap-3">
                                <span class="icon-frame-soft"><x-ui.icon name="play" class="size-4" /></span>
                                <div>
                                    <p class="text-sm font-black text-ink">{{ $video->title }}</p>
                                    <p class="mt-1 text-[11px] leading-6 text-muted">اسلاگ: <span dir="ltr">{{ $video->slug }}</span></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 font-bold text-ink">{{ $video->course?->title ?? '—' }}</td>
                        <td class="p-4 text-center font-mono text-ink" dir="ltr">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) : '—' }}</td>
                        <td class="p-4 text-center font-black text-teal">{{ $video->completion_threshold_percent ?: config('broca.video_completion_threshold') }}٪</td>
                        <td class="p-4 text-center">
                            <form method="post" action="{{ route('admin.free-items.update', ['type' => 'videos', 'id' => $video->id]) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="designated" value="{{ $video->is_free_designated ? 0 : 1 }}">
                                <button type="submit" class="{{ $video->is_free_designated ? 'badge-success' : 'badge-soft' }}">
                                    {{ $video->is_free_designated ? 'رایگان ✓' : 'ویژه اشتراک' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-4">
                            <span class="{{ $video->status === 'published' ? 'badge-success' : ($video->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">
                                {{ $video->status === 'published' ? 'منتشر شده' : ($video->status === 'in_review' ? 'در بازبینی' : ($video->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                            </span>
                        </td>
                        <td class="p-4 text-left">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.videos.edit', $video) }}" class="button-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.videos.destroy', $video) }}" onsubmit="return confirm('آیا از حذف این ویدیو اطمینان دارید؟');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10">
                            <div class="empty-state">
                                <h2 class="empty-state-title">هنوز ویدیویی ثبت نشده است</h2>
                                <p class="empty-state-copy">ویدیوهای دوره، معیار اصلی تجربه آموزشی هستند. اولین درس ویدیویی را با زمان و آستانه تکمیل مناسب ثبت کنید.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $videos->links() }}</div>
</section>
@endsection
