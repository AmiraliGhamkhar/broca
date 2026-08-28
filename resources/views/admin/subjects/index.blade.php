@extends('layouts.app')

@section('title', 'مدیریت درس‌نامه‌ها و مباحث — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">مباحث و شاخه‌های علوم پایه پزشکی</h2>
            <p class="text-xs text-broca-slate mt-1">دسته‌بندی کلان دوره‌ها (فیزیولوژی، آناتومی، نورولوژی، فارماکولوژی و...)</p>
        </div>
        <a href="{{ route('admin.subjects.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-black text-cream hover:bg-coral transition-all shadow-sm">
            + افزودن مبحث جدید
        </a>
    </div>

    <!-- Subjects Grid / Table -->
    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">عنوان شاخه / درس‌نامه</th>
                    <th class="p-3.5 font-black">اسلاگ (URL)</th>
                    <th class="p-3.5 font-black text-center">تعداد دوره‌ها</th>
                    <th class="p-3.5 font-black text-center">ترتیب</th>
                    <th class="p-3.5 font-black">نمایش عمومی</th>
                    <th class="p-3.5 font-black text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($subjects as $subj)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <span class="font-black text-sm">{{ $subj->name }}</span>
                            @if ($subj->description)
                                <span class="text-[11px] text-broca-slate block mt-0.5">{{ $subj->description }}</span>
                            @endif
                        </td>
                        <td class="p-3.5 font-mono text-[11px] text-broca-slate" dir="ltr">{{ $subj->slug }}</td>
                        <td class="p-3.5 text-center font-bold text-ink">
                            <span class="rounded-full px-2.5 py-0.5 bg-ink/5 text-xs">{{ $subj->courses_count }} دوره</span>
                        </td>
                        <td class="p-3.5 text-center font-bold text-broca-slate">{{ $subj->sort_order }}</td>
                        <td class="p-3.5">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $subj->is_visible ? 'bg-teal/10 text-teal' : 'bg-coral/10 text-coral' }}">
                                {{ $subj->is_visible ? 'نمایش در کاتالوگ ✓' : 'مخفی' }}
                            </span>
                        </td>
                        <td class="p-3.5 text-left">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.subjects.edit', $subj) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">ویرایش</a>
                                <form method="post" action="{{ route('admin.subjects.destroy', $subj) }}" onsubmit="return confirm('آیا از حذف این مبحث اطمینان دارید؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="px-3 py-1.5 rounded-full border border-coral/30 text-coral font-bold hover:bg-coral/10">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-sm text-broca-slate">مبحثی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
