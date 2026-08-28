@extends('layouts.app')

@section('title', 'مدیریت دسته‌ها و فلش‌کارت‌های مرور فاصله‌دار — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">دسته‌ها و فلش‌کارت‌های مرور فاصله‌دار (SM-2)</h2>
            <p class="text-xs text-broca-slate mt-1">مدیریت کارت‌های حافظه، روی کارت، پشت کارت، نکات کلیدی و وضعیت سهمیه رایگان</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.flashcards.decks.create') }}" class="rounded-full bg-ink px-5 py-2.5 text-xs font-black text-cream hover:bg-coral transition-all shadow-sm">
                + ساخت دسته کارت جدید
            </a>
            @if ($selectedDeck)
                <a href="{{ route('admin.flashcards.cards.create', ['deck_id' => $selectedDeck->id]) }}" class="rounded-full bg-sun px-5 py-2.5 text-xs font-black text-ink hover:bg-cream transition-all shadow-sm">
                    + افزودن کارت به «{{ $selectedDeck->title }}»
                </a>
            @endif
        </div>
    </div>

    <!-- 2 Column Layout: Decks on Left/Top, Cards of selected deck on Right -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Decks List -->
        <div class="space-y-4">
            <h3 class="text-sm font-black text-ink flex items-center justify-between pb-2 border-b border-broca-sand">
                <span>🗂 دسته‌های کارت (Decks)</span>
                <span class="text-xs text-broca-slate">{{ $decks->total() }} دسته</span>
            </h3>

            <div class="space-y-2.5">
                @forelse ($decks as $deck)
                    <div class="p-4 rounded-2xl border transition-all {{ ($selectedDeck && $selectedDeck->id === $deck->id) ? 'border-coral bg-coral/5 shadow-sm' : 'border-broca-sand bg-white/70 hover:bg-white' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[11px] font-bold text-coral">{{ $deck->course->title ?? '—' }}</span>
                                <h4 class="text-sm font-black text-ink mt-0.5">{{ $deck->title }}</h4>
                                <span class="text-[11px] text-broca-slate block mt-1">{{ $deck->cards_count }} کارت مرور</span>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $deck->status === 'published' ? 'bg-teal/10 text-teal' : 'bg-sun text-ink' }}">
                                {{ $deck->status }}
                            </span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-broca-sand flex items-center justify-between text-xs">
                            <a href="{{ route('admin.flashcards.index', ['deck_id' => $deck->id]) }}" class="font-black text-coral underline">
                                مدیریت کارت‌ها ({{ $deck->cards_count }}) →
                            </a>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.flashcards.decks.edit', $deck) }}" class="text-broca-slate hover:text-ink font-bold">ویرایش</a>
                                <form method="post" action="{{ route('admin.flashcards.decks.destroy', $deck) }}" onsubmit="return confirm('حذف دسته کارت؟');">
                                    @csrf @method('delete')
                                    <button type="submit" class="text-coral hover:underline font-bold">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-broca-slate p-4 text-center">هنوز دسته‌ای ساخته نشده است.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $decks->links() }}</div>
        </div>

        <!-- Cards of Selected Deck -->
        <div class="lg:col-span-2">
            @if ($selectedDeck)
                <div class="surface-panel p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-broca-sand">
                        <div>
                            <span class="text-xs font-bold text-coral">{{ $selectedDeck->course->title ?? '' }}</span>
                            <h3 class="text-lg font-black text-ink">کارت‌های دسته: {{ $selectedDeck->title }}</h3>
                        </div>
                        <a href="{{ route('admin.flashcards.cards.create', ['deck_id' => $selectedDeck->id]) }}"
                           class="rounded-full bg-ink px-4 py-2 text-xs font-black text-cream hover:bg-coral transition-all">
                            + افزودن کارت جدید
                        </a>
                    </div>

                    <div class="mt-4 divide-y divide-broca-sand">
                        @forelse ($cards as $card)
                            <div class="py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-broca-slate">سوال/مفهوم (روی کارت):</span>
                                            <span class="text-xs font-black text-ink">{{ $card->front }}</span>
                                        </div>
                                        <div class="flex items-start gap-2 text-xs text-broca-slate">
                                            <span class="font-bold">پاسخ (پشت کارت):</span>
                                            <span class="text-ink/80 leading-5">{{ $card->back }}</span>
                                        </div>
                                        @if ($card->hint)
                                            <p class="text-[11px] text-coral font-bold">نکته راهنما: {{ $card->hint }}</p>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <form method="post" action="{{ route('admin.free-items.update', ['type' => 'flashcards', 'id' => $card->id]) }}">
                                            @csrf @method('patch')
                                            <input type="hidden" name="designated" value="{{ $card->is_free_designated ? 0 : 1 }}">
                                            <button type="submit" class="rounded-full px-2.5 py-0.5 text-[11px] font-black transition-all {{ $card->is_free_designated ? 'bg-teal text-cream' : 'bg-broca-sand text-broca-slate' }}">
                                                {{ $card->is_free_designated ? 'رایگان ✓' : 'ویژه' }}
                                            </button>
                                        </form>

                                        <div class="flex items-center gap-2 text-xs">
                                            <a href="{{ route('admin.flashcards.cards.edit', $card) }}" class="px-2.5 py-1 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">ویرایش</a>
                                            <form method="post" action="{{ route('admin.flashcards.cards.destroy', $card) }}" onsubmit="return confirm('حذف این کارت؟');">
                                                @csrf @method('delete')
                                                <button type="submit" class="text-coral font-bold hover:underline">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <p class="text-sm font-bold text-broca-slate">این دسته هنوز کارتی ندارد.</p>
                                <a href="{{ route('admin.flashcards.cards.create', ['deck_id' => $selectedDeck->id]) }}" class="mt-3 inline-block font-black text-coral underline text-xs">
                                    اولین کارت را ایجاد کنید
                                </a>
                            </div>
                        @endforelse
                    </div>

                    @if ($cards instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="mt-4">{{ $cards->links() }}</div>
                    @endif
                </div>
            @else
                <div class="surface-panel p-12 text-center text-broca-slate">
                    <span class="text-4xl block mb-3">👈</span>
                    <h4 class="text-base font-black text-ink">یک دسته کارت را از ستون راست انتخاب کنید</h4>
                    <p class="text-xs mt-1">برای مشاهده، افزودن و مدیریت تک‌تک کارت‌های حافظه، ابتدا روی یکی از دسته‌ها کلیک کنید.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
