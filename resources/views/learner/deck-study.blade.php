@extends('layouts.app')

@section('title', $deck->title . ' — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-5xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28" x-data="flashcardStudy()">
    <a href="{{ route('courses.show', $deck->course) }}" class="text-sm font-black text-coral underline-offset-4 hover:underline">بازگشت به دوره</a>
    <p class="mt-12 text-sm font-black text-coral">مرور فاصله‌دار</p>
    <h1 class="mt-4 text-5xl font-black sm:text-7xl">{{ $deck->title }}</h1>
    <p class="mt-5 leading-8 text-ink/65">هر بار یک کارت را بخوان، پاسخ را برگردان و کیفیت یادآوری را ثبت کن.</p>

    <div class="mt-14 grid gap-5 md:grid-cols-2">
        @forelse ($cards as $card)
            <article class="rounded-[2rem] bg-sun p-7" x-data="{ revealed: false, submitted: false }">
                <p class="text-lg font-black">{{ $card->front }}</p>
                <button type="button" x-on:click="revealed = true" x-show="!revealed" class="mt-8 rounded-full bg-ink px-5 py-3 font-black text-cream">نمایش پاسخ</button>
                <div x-show="revealed" x-cloak>
                    <p class="mt-8 border-t border-ink/15 pt-6 leading-8">{{ $card->back }}</p>
                    <div class="mt-7 flex flex-wrap gap-2" x-show="!submitted">
                        @foreach ([0 => 'فراموش کردم', 3 => 'سخت بود', 4 => 'خوب بود', 5 => 'آسان بود'] as $quality => $label)
                            <button type="button" x-on:click="submitReview({{ $card->id }}, {{ $quality }}).then(ok => submitted = ok)" class="rounded-full border border-ink px-4 py-2 text-sm font-black hover:bg-ink hover:text-cream">{{ $label }}</button>
                        @endforeach
                    </div>
                    <p x-show="submitted" x-cloak class="mt-6 text-sm font-black text-teal">ثبت شد؛ کارت بعدی را ادامه بده.</p>
                </div>
            </article>
        @empty
            <div class="rounded-[2rem] border-2 border-ink p-8"><h2 class="text-2xl font-black">کارت آماده‌ای برای مرور نیست</h2><p class="mt-3 leading-8 text-ink/65">با انتشار کارت‌های این دسته، مرور اینجا فعال می‌شود.</p></div>
        @endforelse
    </div>
</section>

@push('scripts')
<script>
    function flashcardStudy() {
        return {
            async submitReview(cardId, quality) {
                const response = await fetch(@js(url('/flashcards')) + '/' + cardId + '/review', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                    credentials: 'same-origin',
                    body: JSON.stringify({ quality }),
                });
                return response.ok;
            },
        };
    }
</script>
@endpush
@endsection
