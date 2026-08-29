@extends('layouts.app')

@section('title', 'مدیریت درس‌نامه‌ها و مباحث — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-ink">مباحث و شاخه‌های علوم پایه پزشکی</h2>
            <p class="text-xs text-muted mt-1">دسته‌بندی کلان دوره‌ها (فیزیولوژی، آناتومی، نورولوژی، فارماکولوژی و...)</p>
        </div>
        <a href="{{ route('admin.subjects.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-bold text-white hover:bg-rausch transition-all shadow-sm">
            + افزودن مبحث جدید
        </a>
    </div>

    <!-- Subjects Grid / Table -->
    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-surface-soft text-right text-muted border-b border-hairline-soft">
                <tr>
                    <th class="p-3.5 font-bold">عنوان شاخه / درس‌نامه</th>
                    <th class="p-3.5 font-bold">اسلاگ (URL)</th>
                    <th class="p-3.5 font-bold text-center">تعداد دوره‌ها</th>
                    <th class="p-3.5 font-bold text-center">ترتیب</th>
                    <th class="p-3.5 font-bold">نمایش عمومی</th>
                    <th class="p-3.5 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($subjects as $subj)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <span class="font-bold text-sm">{{ $subj->name }}</span>
                            @if ($subj->description)
                                <span class="text-[11px] text-muted block mt-0.5">{{ $subj->description }}</span>
                            @endif
                        </td>
                        <td class="p-3.5 font-mono text-[11px] text-muted" dir="ltr">{{ $subj->slug }}</td>
                        <td class="p-3.5 text-center font-bold text-ink">
                            <span class="rounded-full px-2.5 py-0.5 bg-surface-soft text-xs">{{ $subj->courses_count }} دوره</span>
                        </td>
                        <td class="p-3.5 text-center font-bold text-muted">{{ $subj->sort_order }}</td>
                        <td class="p-3.5">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $subj->is_visible ? 'bg-teal/10 text-teal' : 'bg-rausch-tint text-rausch' }}">
                                {{ $subj->is_visible ? 'نمایش در کاتالوگ ✓' : 'مخفی' }}
                            </span>
                        </td>
                        <td class="p-3.5 text-left">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.subjects.edit', $subj) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-surface-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.subjects.destroy', $subj) }}" onsubmit="return confirm('آیا از حذف این مبحث اطمینان دارید؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="px-3 py-1.5 rounded-full border border-rausch/30 text-rausch font-bold hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-sm text-muted">مبحثی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
