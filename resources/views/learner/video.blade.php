@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-5xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28" x-data="videoPlayback()">
    <a href="{{ route('courses.show', $course) }}" class="text-sm font-black text-coral underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">بازگشت به دوره</a>
    <p class="mt-12 text-sm font-black text-coral">{{ $course->subject?->name }}</p>
    <h1 class="mt-4 max-w-4xl text-5xl font-black leading-tight sm:text-7xl">{{ $video->title }}</h1>

    @if ($video->description)
        <p class="mt-6 max-w-3xl text-lg leading-9 text-ink/65">{{ $video->description }}</p>
    @endif

    <div class="mt-12 rounded-[2rem] bg-ink p-6 text-cream sm:p-10">
        <template x-if="manifest">
            <div>
                <video class="aspect-video w-full rounded-[1.25rem] bg-black" controls playsinline preload="metadata" x-bind:src="manifest" aria-label="پخش {{ $video->title }}"></video>
                <p class="mt-5 text-sm text-cream/65">اگر پخش ویدیو شروع نشد، اتصال سرویس ویدیو هنوز در محیط نمونه فعال نشده است.</p>
            </div>
        </template>

        <template x-if="!manifest">
            <div class="flex min-h-64 flex-col items-center justify-center text-center">
                <span class="grid size-16 place-items-center rounded-full bg-coral text-2xl font-black" aria-hidden="true">▶</span>
                <h2 class="mt-6 text-2xl font-black">ویدیو آمادهٔ یادگیری است</h2>
                @if ($canPlay)
                    <p class="mt-3 max-w-md leading-8 text-cream/65">برای دریافت مجوز کوتاه‌مدت پخش، دکمهٔ زیر را بزن.</p>
                    <button type="button" x-on:click="loadPlayback" x-bind:disabled="loading" class="mt-6 rounded-full bg-sun px-6 py-3 font-black text-ink disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!loading">دریافت مجوز پخش</span>
                        <span x-show="loading" x-cloak>در حال آماده‌سازی…</span>
                    </button>
                @else
                    <p class="mt-3 max-w-md leading-8 text-cream/65">این ویدیو برای حساب تو باز نیست. ابتدا در دوره ثبت‌نام کن و برای محتوای ویژه اشتراک فعال داشته باش.</p>
                    <a href="{{ route('courses.show', $course) }}" class="mt-6 rounded-full bg-sun px-6 py-3 font-black text-ink">مشاهدهٔ دوره</a>
                @endif
                <p x-show="error" x-cloak class="mt-5 text-sm font-bold text-coral" role="alert" x-text="error"></p>
            </div>
        </template>
    </div>

    <div class="mt-8 flex flex-wrap gap-3 text-sm font-bold">
        <span class="rounded-full bg-sun px-4 py-2">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) : 'زمان نمونه' }}</span>
        <span class="rounded-full bg-teal px-4 py-2">تکمیل با مشاهدهٔ {{ $threshold }}٪</span>
        <span class="rounded-full bg-plum px-4 py-2 text-cream">پخش با مجوز کوتاه‌مدت</span>
    </div>
</section>

@push('scripts')
<script>
    function videoPlayback() {
        return {
            loading: false,
            manifest: null,
            error: '',
            async loadPlayback() {
                this.loading = true;
                this.error = '';

                try {
                    const response = await fetch(@js(route('videos.playback', $video)), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error('این ویدیو در حال حاضر برای حساب تو در دسترس نیست.');
                    }

                    this.manifest = payload.playback_url;
                } catch (error) {
                    this.error = error.message || 'دریافت مجوز پخش ممکن نشد.';
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
