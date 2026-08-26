@extends('layouts.app')

@section('title', $video->title . ' — ' . __('app.name'))

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
                <video x-ref="player" class="aspect-video w-full rounded-[1.25rem] bg-black" controls playsinline preload="metadata" x-bind:src="manifest" aria-label="پخش {{ $video->title }}"></video>

                <div class="mt-5 flex flex-wrap items-center gap-4">
                    <div class="h-2 w-40 rounded-full bg-cream/20 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progressPercent">
                        <div class="h-2 rounded-full bg-sun transition-all" x-bind:style="`width:${progressPercent}%`"></div>
                    </div>
                    <p class="text-sm text-cream/65"><span x-text="progressPercent">0</span>٪ دیده‌شده</p>
                    <p x-show="completed" x-cloak class="text-sm font-black text-sun">✓ این ویدیو برای تو تکمیل شده است.</p>
                </div>
                <p class="mt-5 text-sm text-cream/65">اگر پخش ویدیو شروع نشد، اتصال سرویس ویدیو هنوز در محیط نمونه فعال نشده است.</p>
            </div>
        </template>

        <template x-if="!manifest">
            <div class="flex min-h-64 flex-col items-center justify-center text-center">
                <span class="grid size-16 place-items-center rounded-full bg-coral text-2xl font-black" aria-hidden="true">▶</span>
                <h2 class="mt-6 text-2xl font-black">ویدیو آمادهٔ یادگیری است</h2>
                @if ($canPlay)
                    <p class="mt-3 max-w-md leading-8 text-cream/65">برای دریافت مجوز کوتاه‌مدت پخش، دکمهٔ زیر را بزن.</p>
                    <button type="button" x-on:click="loadPlayback" x-bind:disabled="loading" class="mt-6 rounded-full bg-sun px-6 py-3 font-black text-ink disabled:cursor-wait disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-cream">
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
            progressPercent: 0,
            completed: false,
            lastSentAt: 0,

            async loadPlayback() {
                this.loading = true;
                this.error = '';

                try {
                    const response = await fetch(@js(route('videos.playback', $video)), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const payload = await response.json();

                    if (!response.ok || !payload.playback_url) {
                        throw new Error('این ویدیو در حال حاضر برای حساب تو در دسترس نیست.');
                    }

                    this.manifest = payload.playback_url;
                    this.$nextTick(() => this.bindPlayer());
                } catch (error) {
                    this.error = error.message || 'دریافت مجوز پخش ممکن نشد.';
                } finally {
                    this.loading = false;
                }
            },

            bindPlayer() {
                const player = this.$refs.player;
                if (!player) {
                    return;
                }

                // Report progress at most every 10 seconds while watching,
                // plus once when the video ends.
                player.addEventListener('timeupdate', () => {
                    const now = Date.now();
                    if (now - this.lastSentAt >= 10000) {
                        this.lastSentAt = now;
                        this.report(Math.floor(player.currentTime));
                    }
                });
                player.addEventListener('ended', () => this.report(Math.ceil(player.duration || player.currentTime)));
            },

            async report(watchedSeconds) {
                if (!watchedSeconds || watchedSeconds < 1) {
                    return;
                }

                try {
                    const response = await fetch(@js(route('videos.progress', $video)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ watched_seconds: watchedSeconds }),
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    this.progressPercent = data.watched_percent ?? this.progressPercent;
                    this.completed = Boolean(data.completed);
                } catch {
                    // Progress reporting is best-effort; never interrupt playback.
                }
            },
        };
    }
</script>
@endpush
@endsection
