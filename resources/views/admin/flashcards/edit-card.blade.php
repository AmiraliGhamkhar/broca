@extends('layouts.app')

@section('title', ($card->exists ? 'ویرایش کارت حافظه' : 'افزودن کارت حافظه جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
            <div>
                <span class="text-xs font-bold text-rausch">{{ $deck->title }} ({{ $deck->course->title ?? '' }})</span>
                <h2 class="text-2xl font-bold text-ink mt-0.5">{{ $card->exists ? 'ویرایش کارت حافظه' : 'افزودن کارت حافظه جدید' }}</h2>
            </div>
            <a href="{{ route('admin.flashcards.index', ['deck_id' => $deck->id]) }}" class="text-xs font-bold text-rausch underline">بازگشت به کارت‌ها</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $card->exists ? route('admin.flashcards.cards.update', $card) : route('admin.flashcards.cards.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($card->exists)
                @method('patch')
            @else
                <input type="hidden" name="flashcard_deck_id" value="{{ $deck->id }}">
            @endif

            <div>
                <label for="front" class="block text-xs font-bold text-ink">روی کارت (پرسش / واژه بالینی / مفهوم کلیدی)</label>
                <textarea id="front" name="front" rows="3" required
                          placeholder="مثال: پتانسیل استراحت غشایی سلول قلبی چقدر است؟"
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">{{ old('front', $card->front) }}</textarea>
            </div>

            <div>
                <label for="back" class="block text-xs font-bold text-ink">پشت کارت (پاسخ تشریحی کامل / تعریف فیزیولوژیک)</label>
                <textarea id="back" name="back" rows="4" required
                          placeholder="مثال: اختلاف پتانسیل الکتریکی در حالت دیاستول حدود منفی ۹۰ میلی‌ولت است که توسط پمپ سدیم-پتاسیم حفظ می‌شود."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white">{{ old('back', $card->back) }}</textarea>
            </div>

            <div>
                <label for="hint" class="block text-xs font-bold text-ink">نکته راهنما (اختیاری - پیش از برگرداندن کارت نمایش داده می‌شود)</label>
                <input type="text" id="hint" name="hint" value="{{ old('hint', $card->hint) }}"
                       placeholder="مثال: توجه به یون پتاسیم در دیاستول"
                       class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="sort_order" class="block text-xs font-bold text-ink">ترتیب در دسته</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $card->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $card->status ?? 'published') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $card->is_free_designated) ? 'checked' : '' }} class="rounded border-ink/20">
                        <span class="text-xs font-bold text-ink">سهمیه رایگان (سقف ۱۰ کارت)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-hairline-soft">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-bold text-white hover:bg-rausch transition-all">
                    {{ $card->exists ? 'ذخیره تغییرات کارت' : 'افزودن کارت به دسته' }}
                </button>
                <a href="{{ route('admin.flashcards.index', ['deck_id' => $deck->id]) }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-muted hover:bg-surface-soft">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
