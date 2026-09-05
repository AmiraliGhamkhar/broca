@extends('layouts.app')

@section('title', $deck->title . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit($deck->description ?: ('مرور فلش‌کارت‌های «' . $deck->title . '» از دوره «' . $deck->course->title . '» در بروکا.'), 155))
@section('meta_author', $deck->reviewer?->name ?: ($deck->author?->name ?: __('app.name')))
@section('robots', 'noindex, follow')

@section('content')
@php($displayTimezone = config('broca.display_timezone'))
<section class="section-shell section-stack section-stack-tight-top" x-data="flashcardStudy()">
    <p x-show="error" x-cloak class="rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-sm font-bold text-rausch" role="alert" x-text="error"></p>
    <p x-show="sessionExpired" x-cloak class="rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-sm font-bold text-rausch" role="alert" aria-live="assertive">
        نشست شما منقضی شده است؛ برای ثبت مرورها دوباره وارد شوید.
    </p>

    <a href="{{ route('courses.show', $deck->course) }}" class="button-secondary">
        <x-ui.icon name="stack" class="size-4" />
        بازگشت به صفحه دوره
    </a>

    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)] xl:items-start">
        <div class="space-y-6">
            <div class="section-intro max-w-4xl">
                <span class="eyebrow">مرور فاصله‌دار</span>
                <p class="mt-4 text-sm font-bold text-rausch">{{ $deck->course->subject?->name }} · {{ $deck->course->title }}</p>
                <h1 class="section-title mt-4">{{ $deck->title }}</h1>
                <p class="section-copy mt-5">هر کارت را بخوانید، پاسخ را نمایش دهید و کیفیت یادآوری را ثبت کنید تا زمان مرور بعدی به‌شکل هوشمند تنظیم شود.</p>
            </div>

            @forelse ($cards as $card)
                <article class="form-panel p-7 sm:p-8" x-data="{ revealed: false, submitted: false }">
                    <div class="flex items-start justify-between gap-4">
                        <span class="badge-soft">کارت {{ $loop->iteration }}</span>
                        <span class="text-xs font-bold text-muted">{{ $card->updated_at?->timezone($displayTimezone)?->format('Y/m/d') ?: 'مرور فعال' }}</span>
                    </div>

                    <h2 class="mt-5 text-2xl font-black leading-9 text-ink">{{ $card->front }}</h2>

                    <button type="button" x-on:click="revealed = true" x-show="!revealed" class="button-primary mt-8">
                        <x-ui.icon name="document" class="size-4" />
                        نمایش پاسخ
                    </button>

                    <div x-show="revealed" x-cloak
                         x-transition:enter="transition duration-300 ease-out"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="card-answer mt-8 border-t border-hairline-soft pt-6">
                        <p class="text-sm leading-8 text-ink">{{ $card->back }}</p>

                        <div class="mt-7">
                            <p class="text-xs font-bold text-muted">کیفیت یادآوری شما از این کارت چطور بود؟</p>
                            <div class="mt-3 flex flex-wrap gap-2" x-show="!submitted">
                                @foreach ([0 => 'فراموش کردم', 3 => 'سخت بود', 4 => 'خوب بود', 5 => 'آسان بود'] as $quality => $label)
                                    <button type="button" x-on:click="submitReview(@js(route('flashcards.review', $card)), {{ $quality }}).then(ok => submitted = ok)" class="button-secondary">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            <p x-show="submitted" x-cloak class="mt-5 text-sm font-bold text-teal" role="status" aria-live="polite">ثبت شد؛ می‌توانید کارت بعدی را ادامه دهید.</p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <h2 class="empty-state-title">فعلاً کارت آماده‌ای برای مرور نیست</h2>
                    <p class="empty-state-copy">وقتی کارت جدید منتشر شود یا مرور بعدی سر برسد، از همین صفحه می‌توانید برنامه مرور را ادامه دهید.</p>
                </div>
            @endforelse
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft space-y-4">
                <h2 class="text-sm font-extrabold text-ink">شناسنامه دسته کارت</h2>
                <dl class="grid gap-3 text-xs leading-6 text-muted">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">نویسنده</dt>
                        <dd class="text-left">{{ $deck->author?->name ?: 'تیم آموزشی بروکا' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">بازبین علمی</dt>
                        <dd class="text-left">{{ $deck->reviewer?->name ?: 'در حال ثبت توسط تیم علمی' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">تعداد کارت آماده</dt>
                        <dd class="text-left">{{ number_format($cards->count()) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">انتشار</dt>
                        <dd class="text-left">{{ $deck->published_at?->timezone($displayTimezone)?->format('Y/m/d') ?: 'منتشرشده برای دانشجویان مجاز' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="editorial-card is-soft">
                <h2 class="text-base font-extrabold text-ink">راهنمای مرور</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="refresh" class="size-5" /></span>
                        <div>
                            <strong>کیفیت یادآوری را صادقانه ثبت کنید</strong>
                            <span>انتخاب شما روی فاصله مرور بعدی اثر می‌گذارد و برای برنامه‌ریزی علمی تکرار استفاده می‌شود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="chart" class="size-5" /></span>
                        <div>
                            <strong>مرورهای کوچک اما پیوسته</strong>
                            <span>بهتر است هر روز همین دسته را در زمان کوتاه مرور کنید تا یادگیری در حافظه بلندمدت تثبیت شود.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>

@push('scripts')
<script>
    function flashcardStudy() {
        return {
            error: '',
            sessionExpired: false,
            async submitReview(reviewUrl, quality) {
                this.error = '';
                this.sessionExpired = false;

                try {
                    const response = await fetch(reviewUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ quality }),
                    });

                    if (!response.ok) {
                        if (response.status === 401 || response.status === 419) {
                            this.sessionExpired = true;
                            this.error = 'برای ادامه ثبت مرور، دوباره وارد حساب شوید.';
                        } else if (response.status === 429) {
                            this.error = 'تعداد درخواست‌ها زیاد است؛ کمی بعد دوباره تلاش کنید.';
                        } else {
                            this.error = 'ثبت مرور انجام نشد؛ دوباره تلاش کنید.';
                        }
                    }

                    return response.ok;
                } catch {
                    this.error = 'ارتباط با سرور برقرار نشد؛ دوباره تلاش کنید.';
                    return false;
                }
            },
        };
    }
</script>
@endpush
@endsection
