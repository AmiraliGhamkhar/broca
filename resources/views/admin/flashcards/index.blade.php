@extends('layouts.app')

@section('title', 'مدیریت دسته‌ها و فلش‌کارت‌های مرور فاصله‌دار — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">مرور فاصله‌دار و حافظه بلندمدت</span>
            <h1 class="section-title mt-4">مدیریت دسته‌ها و فلش‌کارت‌ها</h1>
            <p class="section-copy mt-5">دسته‌های فلش‌کارت و کارت‌های هر دسته را با شفافیت در وضعیت انتشار، سهمیه رایگان و توضیح محتوایی مدیریت کنید.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="stack" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">استاندارد مرور</p>
                <p class="mt-2 text-xs leading-7 text-muted">کارت خوب باید یک مفهوم روشن، پاسخ دقیق و در صورت نیاز یک سرنخ کوتاه داشته باشد تا مرور سریع و موثر بماند.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mt-10">
        <a href="{{ route('admin.flashcards.decks.create') }}" class="button-primary">
            <x-ui.icon name="stack" class="size-4" />
            ساخت دسته کارت جدید
        </a>
        @if ($selectedDeck)
            <a href="{{ route('admin.flashcards.cards.create', ['deck_id' => $selectedDeck->id]) }}" class="button-secondary">
                <x-ui.icon name="document" class="size-4" />
                افزودن کارت به «{{ $selectedDeck->title }}»
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mt-8">
        <div class="space-y-4 lg:col-span-1">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold text-ink">دسته‌های کارت</h2>
                <span class="badge-soft">{{ $decks->total() }} دسته</span>
            </div>

            <div class="space-y-3">
                @forelse ($decks as $deck)
                    <article class="editorial-card {{ ($selectedDeck && $selectedDeck->id === $deck->id) ? 'ring-2 ring-rausch/25 border-rausch/20' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold text-rausch">{{ $deck->course->title ?? '—' }}</p>
                                <h3 class="mt-2 text-sm font-black text-ink">{{ $deck->title }}</h3>
                                <p class="mt-2 text-[11px] leading-6 text-muted">{{ $deck->cards_count }} کارت مرور</p>
                            </div>
                            <span class="{{ $deck->status === 'published' ? 'badge-success' : ($deck->status === 'in_review' ? 'badge-neutral' : 'badge-soft') }}">{{ $deck->status === 'published' ? 'منتشر شده' : ($deck->status === 'in_review' ? 'در بازبینی' : ($deck->status === 'draft' ? 'پیش‌نویس' : 'بایگانی')) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-5 pt-5 border-t border-hairline-soft text-xs">
                            <a href="{{ route('admin.flashcards.index', ['deck_id' => $deck->id]) }}" class="font-bold text-rausch underline">مدیریت کارت‌ها</a>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.flashcards.decks.edit', $deck) }}" class="button-soft">ویرایش</a>
                                <form method="post" action="{{ route('admin.flashcards.decks.destroy', $deck) }}" onsubmit="return confirm('حذف دسته کارت؟');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded-full border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <h2 class="empty-state-title">هنوز دسته‌ای ساخته نشده است</h2>
                        <p class="empty-state-copy">اولین دسته فلش‌کارت را ایجاد کنید تا مرور فاصله‌دار دوره‌ها آغاز شود.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $decks->links() }}</div>
        </div>

        <div class="lg:col-span-2">
            @if ($selectedDeck)
                <div class="form-panel p-7 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-hairline-soft">
                        <div>
                            <p class="text-xs font-bold text-rausch">{{ $selectedDeck->course->title ?? '' }}</p>
                            <h2 class="text-xl font-black text-ink mt-2">کارت‌های دسته: {{ $selectedDeck->title }}</h2>
                        </div>
                        <a href="{{ route('admin.flashcards.cards.create', ['deck_id' => $selectedDeck->id]) }}" class="button-primary">
                            <x-ui.icon name="document" class="size-4" />
                            افزودن کارت
                        </a>
                    </div>

                    <div class="divide-y divide-broca-sand mt-4">
                        @forelse ($cards as $card)
                            <article class="py-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 space-y-3">
                                        <div>
                                            <p class="text-[11px] font-bold text-muted">روی کارت</p>
                                            <p class="mt-1 text-sm font-black leading-7 text-ink">{{ $card->front }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold text-muted">پشت کارت</p>
                                            <p class="mt-1 text-xs leading-6 text-ink">{{ $card->back }}</p>
                                        </div>
                                        @if ($card->hint)
                                            <div class="meta-card is-soft">
                                                <p class="text-[11px] font-bold text-ink">سرنخ</p>
                                                <p class="mt-2 text-xs leading-6 text-muted">{{ $card->hint }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <form method="post" action="{{ route('admin.free-items.update', ['type' => 'flashcards', 'id' => $card->id]) }}">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="designated" value="{{ $card->is_free_designated ? 0 : 1 }}">
                                            <button type="submit" class="{{ $card->is_free_designated ? 'badge-success' : 'badge-soft' }}">
                                                {{ $card->is_free_designated ? 'رایگان ✓' : 'ویژه اشتراک' }}
                                            </button>
                                        </form>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.flashcards.cards.edit', $card) }}" class="button-soft">ویرایش</a>
                                            <form method="post" action="{{ route('admin.flashcards.cards.destroy', $card) }}" onsubmit="return confirm('حذف این کارت؟');">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="rounded-full border border-rausch/25 px-4 py-2 text-xs font-bold text-rausch transition hover:bg-rausch-tint">حذف</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state my-4">
                                <h2 class="empty-state-title">این دسته هنوز کارتی ندارد</h2>
                                <p class="empty-state-copy">اولین کارت را با روی کارت روشن، پشت کارت دقیق و سرنخ اختیاری اضافه کنید.</p>
                                <a href="{{ route('admin.flashcards.cards.create', ['deck_id' => $selectedDeck->id]) }}" class="button-primary mt-6">
                                    <x-ui.icon name="document" class="size-4" />
                                    ثبت اولین کارت
                                </a>
                            </div>
                        @endforelse
                    </div>

                    @if ($cards instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="mt-6">{{ $cards->links() }}</div>
                    @endif
                </div>
            @else
                <div class="empty-state h-full">
                    <h2 class="empty-state-title">یک دسته کارت را انتخاب کنید</h2>
                    <p class="empty-state-copy">برای مشاهده و مدیریت کارت‌ها، ابتدا یکی از دسته‌ها را از ستون کناری انتخاب کنید.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
