@extends('layouts.app')

@section('title', ($card->exists ? 'ویرایش کارت حافظه' : 'افزودن کارت حافظه جدید') . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="eyebrow">{{ $deck->title }} · {{ $deck->course->title ?? '' }}</span>
                    <h1 class="text-2xl font-black text-ink mt-4">{{ $card->exists ? 'ویرایش کارت حافظه' : 'افزودن کارت حافظه جدید' }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">روی کارت، پاسخ، سرنخ و وضعیت انتشار را طوری تنظیم کنید که مرور سریع و علمی باقی بماند.</p>
                </div>
                <a href="{{ route('admin.flashcards.index', ['deck_id' => $deck->id]) }}" class="button-secondary">بازگشت به کارت‌ها</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ $card->exists ? route('admin.flashcards.cards.update', $card) : route('admin.flashcards.cards.store') }}" class="mt-6 space-y-6">
                @csrf
                @if ($card->exists)
                    @method('patch')
                @else
                    <input type="hidden" name="flashcard_deck_id" value="{{ $deck->id }}">
                @endif

                <div>
                    <label for="front" class="block text-xs font-bold text-ink mb-1.5">روی کارت</label>
                    <textarea id="front" name="front" rows="3" required placeholder="پرسش، واژه یا مفهوم کلیدی" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('front', $card->front) }}</textarea>
                </div>

                <div>
                    <label for="back" class="block text-xs font-bold text-ink mb-1.5">پشت کارت</label>
                    <textarea id="back" name="back" rows="5" required placeholder="پاسخ دقیق، تعریف یا توضیح کوتاه" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('back', $card->back) }}</textarea>
                </div>

                <div>
                    <label for="hint" class="block text-xs font-bold text-ink mb-1.5">سرنخ کوتاه (اختیاری)</label>
                    <input type="text" id="hint" name="hint" value="{{ old('hint', $card->hint) }}" placeholder="مثال: به نقش پتاسیم توجه کنید" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب در دسته</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $card->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-bold text-ink mb-1.5">وضعیت</label>
                        <select id="status" name="status" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                            @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                                <option value="{{ $st }}" @selected(old('status', $card->status ?? 'published') === $st)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4">
                        <label class="flex items-start gap-3 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $card->is_free_designated) ? 'checked' : '' }} class="mt-0.5 rounded border-ink/20">
                            <span>این کارت در سهمیه رایگان قرار بگیرد.</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        {{ $card->exists ? 'ذخیره تغییرات کارت' : 'افزودن کارت به دسته' }}
                    </button>
                    <a href="{{ route('admin.flashcards.index', ['deck_id' => $deck->id]) }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">چک‌لیست کارت موثر</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="spark" class="size-5" /></span>
                        <div>
                            <strong>یک ایده در هر کارت</strong>
                            <span>روی کارت را کوتاه و متمرکز نگه دارید تا بازیابی ذهنی سریع‌تر انجام شود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="document" class="size-5" /></span>
                        <div>
                            <strong>پاسخ دقیق و مختصر</strong>
                            <span>پشت کارت باید روشن، قابل فهم و مناسب مرور روزانه باشد.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection
