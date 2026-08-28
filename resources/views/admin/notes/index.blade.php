@extends('layouts.app')

@section('title', 'مدیریت جزوات آموزشی — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">جزوات و خلاصه دروس (PDF)</h2>
            <p class="text-xs text-broca-slate mt-1">مدیریت فایل‌های دانلودی خصوصی، انتساب به دوره‌ها و تعیین سهمیه رایگان</p>
        </div>
        <a href="{{ route('admin.notes.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-black text-cream hover:bg-coral transition-all shadow-sm">
            + افزودن جزوه جدید
        </a>
    </div>

    <!-- Filters -->
    <div class="surface-panel mt-6 p-4">
        <form method="get" action="{{ route('admin.notes.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجو در عنوان جزوه..."
                   class="flex-1 min-w-[200px] rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">

            <select name="course_id" class="rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">
                <option value="">همه دوره‌ها</option>
                @foreach ($courses as $c)
                    <option value="{{ $c->id }}" {{ (string)request('course_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->title }}</option>
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
            @if (request()->hasAny(['q', 'course_id', 'status']))
                <a href="{{ route('admin.notes.index') }}" class="text-xs text-coral font-bold underline">پاک کردن</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">عنوان جزوه</th>
                    <th class="p-3.5 font-black">دوره مربوطه</th>
                    <th class="p-3.5 font-black">مسیر ذخیره خصوصی</th>
                    <th class="p-3.5 font-black text-center">سهمیه رایگان</th>
                    <th class="p-3.5 font-black">وضعیت</th>
                    <th class="p-3.5 font-black text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($notes as $note)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <div class="flex items-center gap-2">
                                <span class="text-base">📄</span>
                                <span class="font-black text-sm">{{ $note->title }}</span>
                            </div>
                            <span class="text-[11px] text-broca-slate block mt-0.5">نویسنده: {{ $note->author->name ?? '—' }} · بازبین: {{ $note->reviewer->name ?? '—' }}</span>
                        </td>
                        <td class="p-3.5 font-bold text-broca-slate">{{ $note->course->title ?? '—' }}</td>
                        <td class="p-3.5 font-mono text-[11px] text-broca-slate" dir="ltr">{{ $note->storage_key }}</td>
                        <td class="p-3.5 text-center">
                            <form method="post" action="{{ route('admin.free-items.update', ['type' => 'notes', 'id' => $note->id]) }}">
                                @csrf @method('patch')
                                <input type="hidden" name="designated" value="{{ $note->is_free_designated ? 0 : 1 }}">
                                <button type="submit" class="rounded-full px-3 py-1 text-xs font-black transition-all {{ $note->is_free_designated ? 'bg-teal text-cream' : 'bg-broca-sand text-broca-slate hover:bg-ink/10' }}">
                                    {{ $note->is_free_designated ? 'رایگان ✓' : 'ویژه اشتراک' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-3.5">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $note->status === 'published' ? 'bg-teal/10 text-teal' : ($note->status === 'in_review' ? 'bg-sun text-ink' : 'bg-broca-sand text-broca-slate') }}">
                                {{ $note->status === 'published' ? 'منتشر شده' : ($note->status === 'in_review' ? 'در بازبینی' : ($note->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                            </span>
                        </td>
                        <td class="p-3.5 text-left">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.notes.edit', $note) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">ویرایش</a>
                                <form method="post" action="{{ route('admin.notes.destroy', $note) }}" onsubmit="return confirm('آیا از حذف این جزوه اطمینان دارید؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="px-3 py-1.5 rounded-full border border-coral/30 text-coral font-bold hover:bg-coral/10">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-sm text-broca-slate">جزوه‌ای با این مشخصات یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $notes->links() }}</div>
</section>
@endsection
