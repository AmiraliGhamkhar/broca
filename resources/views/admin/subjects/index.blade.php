@extends('layouts.app')

@section('title', 'مدیریت درس‌نامه‌ها و مباحث — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">ساختار کاتالوگ</span>
            <h1 class="section-title mt-4">مباحث و درس‌نامه‌ها</h1>
            <p class="section-copy mt-5">موضوعات مادر دوره‌ها در اینجا تعریف می‌شوند تا مسیر عمومی سایت، کاتالوگ و ارتباط محتواها با هم منسجم بماند.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="stack" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">اهمیت این لایه</p>
                <p class="mt-2 text-xs leading-7 text-muted">مبحث خوب تعریف‌شده، فهم بهتر کاتالوگ را برای کاربر ایجاد می‌کند و پایه نمایش دوره‌ها، وبلاگ و مسیرهای یادگیری است.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">تعداد مباحث</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($subjects->count()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">نمایش عمومی</p>
            <p class="mt-3 text-3xl font-black text-teal">{{ number_format($subjects->where('is_visible', true)->count()) }}</p>
        </div>
        <div class="meta-card is-soft flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-ink">اقدام سریع</p>
                <p class="mt-2 text-sm leading-7 text-muted">موضوع جدیدی به ساختار کاتالوگ اضافه کنید.</p>
            </div>
            <a href="{{ route('admin.subjects.create') }}" class="button-primary shrink-0">
                <x-ui.icon name="stack" class="size-4" />
                افزودن مبحث
            </a>
        </div>
    </div>

    <div class="surface-panel mt-8 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="border-b border-hairline-soft bg-surface-soft text-right text-muted">
                <tr>
                    <th class="p-4 font-bold">مبحث</th>
                    <th class="p-4 font-bold">اسلاگ</th>
                    <th class="p-4 font-bold text-center">تعداد دوره‌ها</th>
                    <th class="p-4 font-bold text-center">ترتیب</th>
                    <th class="p-4 font-bold">نمایش عمومی</th>
                    <th class="p-4 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($subjects as $subj)
                    <tr class="align-top hover:bg-white/50">
                        <td class="p-4">
                            <p class="text-sm font-black text-ink">{{ $subj->name }}</p>
                            @if ($subj->description)
                                <p class="mt-1 text-[11px] leading-6 text-muted">{{ $subj->description }}</p>
                            @endif
                        </td>
                        <td class="p-4 font-mono text-[11px] text-muted" dir="ltr">{{ $subj->slug }}</td>
                        <td class="p-4 text-center"><span class="badge-soft">{{ $subj->courses_count }} دوره</span></td>
                        <td class="p-4 text-center font-black text-ink">{{ $subj->sort_order }}</td>
                        <td class="p-4">
                            <span class="{{ $subj->is_visible ? 'badge-success' : 'badge-soft' }}">{{ $subj->is_visible ? 'نمایش در کاتالوگ' : 'مخفی' }}</span>
                        </td>
                        <td class="p-4 text-left">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.subjects.edit', $subj) }}" class="button-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.subjects.destroy', $subj) }}" onsubmit="return confirm('آیا از حذف این مبحث اطمینان دارید؟');">
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
                                <h2 class="empty-state-title">هنوز مبحثی ثبت نشده است</h2>
                                <p class="empty-state-copy">برای ساخت کاتالوگ حرفه‌ای، موضوعات مادر را تعریف کنید تا دوره‌ها و محتواها زیر ساختاری روشن نمایش داده شوند.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
