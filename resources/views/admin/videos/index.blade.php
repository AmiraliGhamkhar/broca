@extends('layouts.app')

@section('title', 'مدیریت ویدیوهای آموزشی — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-ink">ویدیوهای آموزشی دوره‌ها</h2>
            <p class="text-xs text-muted mt-1">مدیریت فایل‌های ویدیویی، آستانه تکمیل مشاهده، اساتید و سهمیه پخش رایگان</p>
        </div>
        <a href="{{ route('admin.videos.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-bold text-white hover:bg-rausch transition-all shadow-sm">
            + افزودن ویدیوی جدید
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-surface-soft text-right text-muted border-b border-hairline-soft">
                <tr>
                    <th class="p-3.5 font-bold">عنوان ویدیو</th>
                    <th class="p-3.5 font-bold">دوره مربوطه</th>
                    <th class="p-3.5 font-bold text-center">مدت زمان</th>
                    <th class="p-3.5 font-bold text-center">آستانه تکمیل</th>
                    <th class="p-3.5 font-bold text-center">سهمیه رایگان</th>
                    <th class="p-3.5 font-bold">وضعیت</th>
                    <th class="p-3.5 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($videos as $video)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🎥</span>
                                <span class="font-bold text-sm">{{ $video->title }}</span>
                            </div>
                            <span class="text-[11px] text-muted block mt-0.5">اسلاگ: {{ $video->slug }}</span>
                        </td>
                        <td class="p-3.5 font-bold text-muted">{{ $video->course?->title ?? '—' }}</td>
                        <td class="p-3.5 text-center font-mono" dir="ltr">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) : '—' }}</td>
                        <td class="p-3.5 text-center font-bold text-teal">{{ $video->completion_threshold_percent ?: config('broca.video_completion_threshold') }}٪</td>
                        <td class="p-3.5 text-center">
                            <form method="post" action="{{ route('admin.free-items.update', ['type' => 'videos', 'id' => $video->id]) }}">
                                @csrf @method('patch')
                                <input type="hidden" name="designated" value="{{ $video->is_free_designated ? 0 : 1 }}">
                                <button type="submit" class="rounded-full px-3 py-1 text-xs font-bold transition-all {{ $video->is_free_designated ? 'bg-teal text-white' : 'bg-surface-soft text-muted hover:bg-surface-strong' }}">
                                    {{ $video->is_free_designated ? 'رایگان ✓' : 'ویژه اشتراک' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-3.5">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $video->status === 'published' ? 'bg-teal/10 text-teal' : ($video->status === 'in_review' ? 'bg-surface-soft text-ink' : 'bg-surface-soft text-muted') }}">
                                {{ $video->status === 'published' ? 'منتشر شده' : ($video->status === 'in_review' ? 'در بازبینی' : ($video->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                            </span>
                        </td>
                        <td class="p-3.5 text-left">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.videos.edit', $video) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-surface-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.videos.destroy', $video) }}" onsubmit="return confirm('آیا از حذف این ویدیو اطمینان دارید؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="px-3 py-1.5 rounded-full border border-rausch/30 text-rausch font-bold hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-sm text-muted">هنوز ویدیویی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $videos->links() }}</div>
</section>
@endsection
