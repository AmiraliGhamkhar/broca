@extends('layouts.app')

@section('title', 'مدیریت دوره‌های آموزشی — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">دوره‌های آموزشی</h2>
            <p class="text-xs text-broca-slate mt-1">مدیریت دوره‌های علوم پزشکی، محتوای دروس، اساتید و فرآیند انتشار</p>
        </div>
        <a href="{{ route('admin.courses.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-black text-cream hover:bg-coral transition-all shadow-sm">
            + افزودن دوره جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="surface-panel mt-6 p-4">
        <form method="get" action="{{ route('admin.courses.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجو در عنوان دوره..."
                   class="flex-1 min-w-[200px] rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">

            <select name="subject_id" class="rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">
                <option value="">همه درس‌نامه‌ها</option>
                @foreach ($subjects as $subj)
                    <option value="{{ $subj->id }}" {{ (string)request('subject_id') === (string)$subj->id ? 'selected' : '' }}>{{ $subj->name }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">
                <option value="">همه وضعیت‌ها</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>پیش‌نویس</option>
                <option value="in_review" {{ request('status') === 'in_review' ? 'selected' : '' }}>در بازبینی</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>منتشر شده</option>
                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>بایگانی</option>
            </select>

            <button type="submit" class="rounded-xl bg-broca-sand px-4 py-2.5 text-xs font-bold text-ink hover:bg-ink hover:text-cream">فیلتر</button>
            @if (request()->hasAny(['q', 'subject_id', 'status']))
                <a href="{{ route('admin.courses.index') }}" class="text-xs text-coral font-bold underline">پاک کردن</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">عنوان دوره</th>
                    <th class="p-3.5 font-black">درس‌نامه</th>
                    <th class="p-3.5 font-black">نویسنده و بازبین</th>
                    <th class="p-3.5 font-black text-center">محتوا</th>
                    <th class="p-3.5 font-black text-center">دانشجویان</th>
                    <th class="p-3.5 font-black">وضعیت انتشار</th>
                    <th class="p-3.5 font-black text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($courses as $course)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full {{ $course->status === 'published' ? 'bg-teal' : 'bg-sun' }}"></span>
                                <a href="{{ route('courses.show', $course) }}" target="_blank" class="hover:text-coral transition-colors font-black text-sm">
                                    {{ $course->title }}
                                </a>
                            </div>
                            <span class="text-[11px] text-broca-slate block mt-0.5">سطح: {{ $course->level ?: 'عمومی' }} · اسلاگ: {{ $course->slug }}</span>
                        </td>
                        <td class="p-3.5 font-bold text-broca-slate">{{ $course->subject->name ?? '—' }}</td>
                        <td class="p-3.5 text-broca-slate">
                            <p class="font-bold text-ink">{{ $course->author->name ?? '—' }}</p>
                            <p class="text-[11px]">بازبین: {{ $course->reviewer->name ?? '—' }}</p>
                        </td>
                        <td class="p-3.5 text-center font-bold">
                            <span title="ویدیوها" class="inline-block px-1.5 py-0.5 rounded bg-ink/5">{{ $course->videos_count }} ویدیو</span>
                            <span title="جزوات" class="inline-block px-1.5 py-0.5 rounded bg-ink/5 mt-0.5">{{ $course->notes_count }} جزوه</span>
                        </td>
                        <td class="p-3.5 text-center font-bold text-ink">
                            {{ number_format($course->enrollments_count) }}
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $course->status === 'published' ? 'bg-teal/10 text-teal' : ($course->status === 'in_review' ? 'bg-sun text-ink' : 'bg-broca-sand text-broca-slate') }}">
                                    {{ $course->status === 'published' ? 'منتشر شده' : ($course->status === 'in_review' ? 'در بازبینی' : ($course->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                                </span>
                                <!-- Quick transition buttons -->
                                <form method="post" action="{{ route('admin.publication.update', ['type' => 'courses', 'id' => $course->id]) }}">
                                    @csrf @method('patch')
                                    @if ($course->status === 'draft')
                                        <input type="hidden" name="status" value="in_review">
                                        <button type="submit" class="text-[11px] font-bold text-coral underline">ارسال به بررسی</button>
                                    @elseif ($course->status === 'in_review')
                                        <input type="hidden" name="status" value="published">
                                        <button type="submit" class="text-[11px] font-bold text-teal underline">تأیید و انتشار</button>
                                    @elseif ($course->status === 'published')
                                        <input type="hidden" name="status" value="archived">
                                        <button type="submit" class="text-[11px] font-bold text-broca-slate underline">بایگانی</button>
                                    @endif
                                </form>
                            </div>
                        </td>
                        <td class="p-3.5 text-left">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">ویرایش</a>
                                <form method="post" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('آیا از حذف این دوره اطمینان دارید؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="px-3 py-1.5 rounded-full border border-coral/30 text-coral font-bold hover:bg-coral/10">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-sm text-broca-slate">دوره‌ای با این مشخصات یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $courses->links() }}</div>
</section>
@endsection
