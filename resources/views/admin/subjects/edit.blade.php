@extends('layouts.app')

@section('title', ($subject->exists ? 'ویرایش مبحث: ' . $subject->name : 'ایجاد مبحث جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.15fr)_minmax(280px,0.85fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="sr-only">فرم مدیریت مبحث</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $subject->exists ? 'ویرایش مبحث / شاخه علمی' : 'ایجاد مبحث جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">نام، توضیح و ترتیب نمایش این مبحث را طوری تنظیم کنید که در کاتالوگ و مسیرهای عمومی سایت واضح و حرفه‌ای دیده شود.</p>
                </div>
                <a href="{{ route('admin.subjects.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $subject->exists ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($subject->exists)
                    @method('patch')
                @endif

                <div>
                    <label for="name" class="block text-xs font-bold text-ink mb-1.5">نام مبحث</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $subject->name) }}" required placeholder="مثال: فیزیولوژی پزشکی" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-ink mb-1.5">توضیحات کوتاه</label>
                    <textarea id="description" name="description" rows="4" placeholder="این مبحث چه دامنه‌ای از موضوعات را پوشش می‌دهد؟" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('description', $subject->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب نمایش در کاتالوگ</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $subject->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div class="flex items-center rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4">
                        <label class="flex items-start gap-3 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', $subject->is_visible) ? 'checked' : '' }} class="mt-0.5 rounded border-ink/20">
                            <span>این مبحث در کاتالوگ و صفحات عمومی نمایش داده شود.</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $subject->exists ? 'ذخیره تغییرات مبحث' : 'ثبت مبحث جدید' }}
                    </button>
                    <a href="{{ route('admin.subjects.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">راهنمای ساختاردهی</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="stack" class="size-5" /></span>
                        <div>
                            <strong>نام دقیق و قابل فهم</strong>
                            <span>عنوان مبحث باید برای دانشجوی پزشکی روشن باشد و بتواند چندین دوره مرتبط را زیر خود جمع کند.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="spark" class="size-5" /></span>
                        <div>
                            <strong>توضیح کوتاه اما راهبردی</strong>
                            <span>این متن در ایجاد حس حرفه‌ای و جهت‌دهی کاربر در کاتالوگ نقش مهمی دارد.</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($subject->exists)
                <div class="editorial-card is-soft">
                    <h2 class="text-sm font-extrabold text-ink">وضعیت فعلی</h2>
                    <div class="grid gap-3 mt-4 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">اسلاگ</span>
                            <span dir="ltr" class="font-mono text-ink">{{ $subject->slug }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-muted">نمایش عمومی</span>
                            <span class="{{ $subject->is_visible ? 'badge-success' : 'badge-soft' }}">{{ $subject->is_visible ? 'بله' : 'خیر' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
