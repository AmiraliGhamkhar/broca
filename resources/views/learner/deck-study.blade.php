@extends('layouts.app')

@section('title', $deck->title . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-5xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28" x-data="flashcardStudy()">
    <p x-show="error" x-cloak class="mb-8 rounded-2xl border-2 border-rausch p-4 font-bold text-rausch" role="alert" x-text="error"></p>
    <a href="{{ route('courses.show', $deck->course) }}" class="text-sm font-bold text-rausch underline-offset-4 hover:underline">بازگشت به دوره</a>
    <p class="mt-12 text-sm font-bold text-rausch">مرور فاصله‌دار</p>
    <h1 class="mt-4 font-display text-4xl sm:text-6xl">{{ $deck->title }}</h1>
    <p class="mt-5 leading-8 text-muted">هر بار یک کارت را بخوان، پاسخ را برگردان و کیفیت یادآوری را ثبت کن.</p>

    <p x-show="sessionExpired" x-cloak class="mt-8 rounded-2xl border-2 border-rausch p-4 font-bold text-rausch" role="alert" aria-live="assertive">
        نشست شما منقضی شده است؛ برای ثبت مرورها دوباره وارد شوید.
    </p>

    <div class="mt-14 grid gap-5 md:grid-cols-2">
        @forelse ($cards as $card)
            <article class="rounded-2xl bg-white border border-hairline-soft p-7 shadow-float" x-data="{ revealed: false, submitted: false }">
                <p class="text-lg font-bold">{{ $card->front }}</p>
                <button type="button" x-on:click="revealed = true" x-show="!revealed" class="mt-8 rounded-full bg-rausch px-5 py-3 font-bold text-white">نمایش پاسخ</button>
                <div x-show="revealed" x-cloak>
                    <p class="mt-8 border-t border-hairline-soft pt-6 leading-8">{{ $card->back }}</p>
                    <div class="mt-7 flex flex-wrap gap-2" x-show="!submitted">
                        @foreach ([0 => 'فراموش کردم', 3 => 'سخت بود', 4 => 'خوب بود', 5 => 'آسان بود'] as $quality => $label)
                            <button type="button" x-on:click="submitReview(@js(route('flashcards.review', $card)), {{ $quality }}).then(ok => submitted = ok)" class="rounded-full border border-ink px-4 py-2 text-sm font-bold hover:bg-ink hover:text-white">{{ $label }}</button>
                        @endforeach
                    </div>
                    <p x-show="submitted" x-cloak class="mt-6 text-sm font-bold text-teal" role="status" aria-live="polite">ثبت شد؛ کارت بعدی را ادامه بده.</p>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-hairline p-8"><h2 class="text-2xl font-bold">کارت آماده‌ای برای مرور نیست</h2><p class="mt-3 leading-8 text-muted">با انتشار کارت‌های این دسته، مرور اینجا فعال می‌شود.</p></div>
        @endforelse
    </div>
</section>

@push('scripts')
<script>
    function flashcardStudy() {
        return {
            error: '',
            async submitReview(reviewUrl, quality) {
                this.error = '';
                try {
                const response = await fetch(reviewUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                    credentials: 'same-origin',
                    body: JSON.stringify({ quality }),
                });
                if (!response.ok) {
                    this.error = response.status === 429 ? 'تعداد درخواست‌ها زیاد است؛ کمی بعد دوباره تلاش کنید.' : 'ثبت مرور انجام نشد؛ دوباره تلاش کنید.';
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
