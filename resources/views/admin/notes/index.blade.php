@extends('layouts.app')

@section('title', 'مدیریت جزوات آموزشی — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">فایل‌های خصوصی و دانلودی</span>
            <h1 class="section-title mt-4">جزوات و خلاصه‌درس‌ها</h1>
            <p class="section-copy mt-5">در این بخش می‌توانید جزوات دوره‌ها را با مسیر ذخیره خصوصی، نویسنده، بازبین علمی، وضعیت انتشار و سهمیه رایگان مدیریت کنید.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="document" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">اصل مهم این بخش</p>
                <p class="mt-2 text-xs leading-7 text-muted">جزوه باید شناسنامه آموزشی روشن، فایل خصوصی معتبر و توضیحی مشخص برای کاربر نهایی داشته باشد تا اعتماد و قابلیت پشتیبانی حفظ شود.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">نتایج فهرست</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($notes->total()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">منتشرشده</p>
            <p class="mt-3 text-3xl font-black text-teal">{{ number_format($notes->getCollection()->where('status', 'published')->count()) }}</p>
        </div>
        <div class="meta-card is-soft flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-ink">اقدام سریع</p>
                <p class="mt-2 text-sm leading-7 text-muted">یک جزوه جدید با فایل خصوصی معتبر اضافه کنید.</p>
            </div>
            <a href="{{ route('admin.notes.create') }}" class="button-primary shrink-0">
                <x-ui.icon name="document" class="size-4" />
                افزودن جزوه
            </a>
        </div>
    </div>

    <div class="surface-panel mt-8 p-4 sm:p-5">
        <form method="get" action="{{ route('admin.notes.index') }}" class="grid gap-3 md:grid-cols-[minmax(0,1.4fr)_260px_220px_auto] md:items-end">
            <div>
                <label for="q" class="block text-[11px] font-bold text-ink mb-1.5">جستجوی عنوان جزوه</label>
                <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="مثال: خلاصه نوروآناتومی" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs">
            </div>
            <div>
                <label for="course_id" class="block text-[11px] font-bold text-ink mb-1.5">دوره</label>
                <select id="course_id" name="course_id" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    <option value="">همه دوره‌ها</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}" @selected((string) request('course_id') === (string) $c->id)>{{ $c->title }}</option>
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
                @if (request()->hasAny(['q', 'course_id', 'status']))
                    <a href="{{ route('admin.notes.index') }}" class="button-soft">پاک کردن</a>
                @endif
            </div>
        </form>
    </div>

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="border-b border-hairline-soft bg-surface-soft text-right text-muted">
                <tr>
                    <th class="p-4 font-bold">جزوه</th>
                    <th class="p-4 font-bold">دوره</th>
                    <th class="p-4 font-bold">شناسنامه علمی</th>
                    <th class="p-4 font-bold">مسیر خصوصی</th>
                    <th class="p-4 font-bold text-center">رایگان</th>
                    <th class="p-4 font-bold">وضعیت</th>
                    <th class="p-4 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($notes as $note)
                    <tr class="align-top hover:bg-white/50">
                        <td class="p-4">
                            <div class="flex items-start gap-3">
                                <span class="icon-frame-soft"><x-ui.icon name="document" class="size-4" /></span>
                                <div>
                                    <p class="text-sm font-black text-ink">{{ $note->title }}</p>
                                    @if ($note->description)
                                        <p class="mt-1 text-[11px] leading-6 text-muted">{{ \Illuminate\Support\Str::limit($note->description, 100) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="p-4 font-bold text-ink">{{ $note->course->title ?? '—' }}</td>
                        <td class="p-4 text-muted leading-6">
                            <p><span class="font-bold text-ink">نویسنده:</span> {{ $note->author->name ?? '—' }}</p>
                            <p class="mt-1"><span class="font-bold text-ink">بازبین:</span> {{ $note->reviewer->name ?? '—' }}</p>
                        </td>
                        <td class="p-4 font-mono text-[11px] text-muted" dir="ltr">{{ $note->storage_key ?: '—' }}</td>
                        <td class="p-4 text-center">
                            <form method="post" action="{{ route('admin.free-items.update', ['type' => 'notes', 'id' => $note->id]) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="designated" value="{{ $note->is_free_designated ? 0 : 1 }}">
                                <button type="submit" class="{{ $note->is_free_designated ? 'badge-success' : 'badge-soft' }}">
                                    {{ $note->is_free_designated ? 'رایگان ✓' : 'ویژه اشتراک' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-4">
                            <span class="{{ $note->status === 'published' ? 'badge-success' : ($note->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">
                                {{ $note->status === 'published' ? 'منتشر شده' : ($note->status === 'in_review' ? 'در بازبینی' : ($note->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}
                            </span>
                        </td>
                        <td class="p-4 text-left">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('admin.notes.edit', $note) }}" class="button-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.notes.destroy', $note) }}" onsubmit="return confirm('آیا از حذف این جزوه اطمینان دارید؟');">
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
                                <h2 class="empty-state-title">جزوه‌ای با این مشخصات پیدا نشد</h2>
                                <p class="empty-state-copy">جزوات جدید را با اتصال به دوره، فایل خصوصی و شناسنامه علمی کامل ثبت کنید تا در دسترس دانشجویان مجاز قرار بگیرند.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $notes->links() }}</div>
</section>
@endsection
