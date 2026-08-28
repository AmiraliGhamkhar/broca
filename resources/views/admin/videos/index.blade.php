@extends('layouts.app')

@section('title', 'مدیریت ویدیوهای آموزشی — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">ویدیوهای آموزشی دوره‌ها</h2>
            <p class="text-xs text-broca-slate mt-1">مدیریت فایل‌های ویدیویی، آستانه تکمیل مشاهده، اساتید و سهمیه پخش رایگان</p>
        </div>
        <a href="{{ route('admin.videos.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-black text-cream hover:bg-coral transition-all shadow-sm">
            + افزودن ویدیوی جدید
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">عنوان ویدیو</th>
                    <th class="p-3.5 font-black">دوره مربوطه</th>
                    <th class="p-3.5 font-black text-center">مدت زمان</th>
                    <th class="p-3.5 font-black text-center">آستانه تکمیل</th>
                    <th class="p-3.5 font-black text-center">سهمیه رایگان</th>
                    <th class="p-3.5 font-black">وضعیت</th>
                    <th class="p-3.5 font-black text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($videos as $video)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🎥</span>
                                <span class="font-black text-sm">{{ $video->title }}</span>
                            </div>
                            <span class="text-[11px] text-broca-slate block mt-0.5">اسلاگ: {{ $video->slug }}</span>
                        </td>
                        <td class="p-3.5 font-bold text-broca-slate">{{ $video->course?->title ?? '—' }}</td>
                        <td class="p-3.5 text-center font-mono" dir="ltr">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) : '—' }}</td>
                        <td class="p-3.5 text-center font-bold text-teal">{{ $video->completion_threshold_percent ?: config('broca.video_completion_threshold') }}٪</td>
                        <td class="p-3.5 text-center">
                            <form method="post" action="{{ route('admin.free-items.update', ['type' => 'videos', 'id' => $video->id]) }}">
                                @csrf @method('patch')
                                <input type="hidden" name="designated" value="{{ $video->is_free_designated ? 0 : 1 }}">
                                <button type="submit" class="rounded-full px-3 py-1 text-xs font-black transition-all {{ $video->is_free_designated ? 'bg-teal text-cream' : 'bg-broca-sand text-broca-slate hover:bg-ink/10' }}">
                                    {{ $video->is_free_designated ? 'رایگان ✓' : 'ویژه اشتراک' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-3.5">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $video->status === 'published' ? 'bg-teal/10 text-teal' : ($video->status === 'in_review' ? 'bg-sun text-ink' : 'bg-broca-sand text-broca-slate') }}">
                                {{ $video->status === 'published' ? 'منتشر شده' : ($video->status === 'in_review' ? 'در بازبینی' : ($video->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                            </span>
                        </td>
                        <td class="p-3.5 text-left">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.videos.edit', $video) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">ویرایش</a>
                                <form method="post" action="{{ route('admin.videos.destroy', $video) }}" onsubmit="return confirm('آیا از حذف این ویدیو اطمینان دارید؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="px-3 py-1.5 rounded-full border border-coral/30 text-coral font-bold hover:bg-coral/10">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-sm text-broca-slate">هنوز ویدیویی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $videos->links() }}</div>
</section>
@endsection
