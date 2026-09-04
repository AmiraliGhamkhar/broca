@extends('layouts.app')

@section('title', 'مدیریت دوره‌های آموزشی — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">مدیریت محتوای اصلی</span>
            <h1 class="section-title mt-4">دوره‌های آموزشی</h1>
            <p class="section-copy mt-5">این صفحه برای مدیریت شناسنامه محتوایی دوره‌ها، اتصال به مبحث، تعیین نویسنده و بازبین علمی و کنترل چرخه انتشار استفاده می‌شود.</p>
        </div>

        <div class="editorial-card is-soft">
            <div class="flex items-start gap-3">
                <span class="icon-frame"><x-ui.icon name="graduation" class="size-5" /></span>
                <div>
                    <p class="text-sm font-extrabold text-ink">تمرکز این بخش</p>
                    <p class="mt-2 text-xs leading-7 text-muted">هر دوره باید عنوان روشن، موضوع مشخص، صاحب محتوا و وضعیت انتشار قابل رهگیری داشته باشد تا هم تجربه عمومی و هم مدیریت تلگرام/ادمین منسجم بماند.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">نتایج فهرست</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($courses->total()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">منتشرشده در این فهرست</p>
            <p class="mt-3 text-3xl font-black text-teal">{{ number_format($courses->getCollection()->where('status', 'published')->count()) }}</p>
        </div>
        <div class="meta-card is-soft flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-ink">اقدام سریع</p>
                <p class="mt-2 text-sm leading-7 text-muted">یک دوره تازه با شناسنامه علمی کامل ثبت کنید.</p>
            </div>
            <a href="{{ route('admin.courses.create') }}" class="button-primary shrink-0">
                <x-ui.icon name="graduation" class="size-4" />
                افزودن دوره
            </a>
        </div>
    </div>

    <div class="surface-panel mt-8 p-4 sm:p-5">
        <form method="get" action="{{ route('admin.courses.index') }}" class="grid gap-3 md:grid-cols-[minmax(0,1.4fr)_220px_220px_auto] md:items-end">
            <div>
                <label for="q" class="block text-[11px] font-bold text-ink mb-1.5">جستجوی عنوان دوره</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="مثال: فیزیولوژی قلب" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs">
            </div>
            <div>
                <label for="subject_id" class="block text-[11px] font-bold text-ink mb-1.5">مبحث</label>
                <select id="subject_id" name="subject_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    <option value="">همه درس‌نامه‌ها</option>
                    @foreach ($subjects as $subj)
                        <option value="{{ $subj->id }}" @selected((string) request('subject_id') === (string) $subj->id)>{{ $subj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-[11px] font-bold text-ink mb-1.5">وضعیت</label>
                <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    <option value="">همه وضعیت‌ها</option>
                    @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="button-secondary">
                    <x-ui.icon name="chart" class="size-4" />
                    اعمال فیلتر
                </button>
                @if (request()->hasAny(['q', 'subject_id', 'status']))
                    <a href="{{ route('admin.courses.index') }}" class="button-soft">پاک کردن</a>
                @endif
            </div>
        </form>
    </div>

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="border-b border-hairline-soft bg-surface-soft text-right text-muted">
                <tr>
                    <th class="p-4 font-bold">دوره</th>
                    <th class="p-4 font-bold">مبحث</th>
                    <th class="p-4 font-bold">شناسنامه علمی</th>
                    <th class="p-4 font-bold text-center">محتوا</th>
                    <th class="p-4 font-bold text-center">دانشجو</th>
                    <th class="p-4 font-bold">انتشار</th>
                    <th class="p-4 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($courses as $course)
                    <tr class="align-top hover:bg-white/50">
                        <td class="p-4">
                            <div class="flex items-start gap-3">
                                <span class="icon-frame-soft icon-frame-sm icon-frame-round mt-0.5 text-[11px] font-black">{{ $loop->iteration }}</span>
                                <div>
                                    <a href="{{ route('courses.show', $course) }}" target="_blank" class="text-sm font-black text-ink transition hover:text-rausch">{{ $course->title }}</a>
                                    <p class="mt-1 text-[11px] leading-6 text-muted">سطح: {{ $course->level ?: 'عمومی' }} · اسلاگ: <span dir="ltr">{{ $course->slug }}</span></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 font-bold text-ink">{{ $course->subject->name ?? '—' }}</td>
                        <td class="p-4 text-muted leading-6">
                            <p><span class="font-bold text-ink">نویسنده:</span> {{ $course->author->name ?? '—' }}</p>
                            <p class="mt-1"><span class="font-bold text-ink">بازبین:</span> {{ $course->reviewer->name ?? '—' }}</p>
                        </td>
                        <td class="p-4 text-center font-bold text-ink">
                            <div class="flex flex-col items-center gap-1">
                                <span class="badge-soft">{{ $course->videos_count }} ویدیو</span>
                                <span class="badge-soft">{{ $course->notes_count }} جزوه</span>
                            </div>
                        </td>
                        <td class="p-4 text-center font-black text-ink">{{ number_format($course->enrollments_count) }}</td>
                        <td class="p-4">
                            <div class="space-y-2">
                                <span class="{{ $course->status === 'published' ? 'badge-success' : ($course->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">
                                    {{ $course->status === 'published' ? 'منتشر شده' : ($course->status === 'in_review' ? 'در بازبینی' : ($course->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                                </span>
                                <form method="post" action="{{ route('admin.publication.update', ['type' => 'courses', 'id' => $course->id]) }}" class="text-[11px]">
                                    @csrf
                                    @method('patch')
                                    @if ($course->status === 'draft')
                                        <input type="hidden" name="status" value="in_review">
                                        <button type="submit" class="font-bold text-rausch underline">ارسال به بازبینی</button>
                                    @elseif ($course->status === 'in_review')
                                        <input type="hidden" name="status" value="published">
                                        <button type="submit" class="font-bold text-teal underline">تأیید و انتشار</button>
                                    @elseif ($course->status === 'published')
                                        <input type="hidden" name="status" value="archived">
                                        <button type="submit" class="font-bold text-muted underline">بایگانی</button>
                                    @endif
                                </form>
                            </div>
                        </td>
                        <td class="p-4 text-left">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="button-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('آیا از حذف این دوره اطمینان دارید؟');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded-full border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10">
                            <div class="empty-state">
                                <h2 class="empty-state-title">دوره‌ای با این مشخصات پیدا نشد</h2>
                                <p class="empty-state-copy">فیلترها را تغییر دهید یا اولین دوره آموزشی را با عنوان، مبحث و شناسنامه علمی کامل ایجاد کنید.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $courses->links() }}</div>
</section>
@endsection
