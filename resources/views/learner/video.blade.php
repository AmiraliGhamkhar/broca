@extends('layouts.app')

@section('title', $video->title . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit($video->description ?: ('مشاهده ویدیوی آموزشی «' . $video->title . '» از دوره «' . $course->title . '» در بروکا.'), 155))
@section('meta_author', $video->reviewer?->name ?: ($video->author?->name ?: __('app.name')))
@section('robots', 'noindex, follow')

@section('content')
@php($displayTimezone = config('broca.display_timezone'))
<section class="section-shell section-stack section-stack-tight-top" x-data="videoPlayback({{ $initialPercent }}, {{ $initialCompleted ? 'true' : 'false' }})">
    <a href="{{ route('courses.show', $course) }}" class="button-secondary">
        <x-ui.icon name="stack" class="size-4" />
        بازگشت به صفحه دوره
    </a>

    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.9fr)] xl:items-start">
        <div class="space-y-8">
            <div class="section-intro max-w-4xl">
                <span class="sr-only">ویدیوی آموزشی خصوصی</span>
                <p class="mt-4 text-sm font-bold text-rausch-text">{{ $course->subject?->name }} · {{ $course->title }}</p>
                <h1 class="section-title mt-4">{{ $video->title }}</h1>
                @if ($video->description)
                    <p class="section-copy mt-5">{{ $video->description }}</p>
                @endif
            </div>

            <div class="surface-panel bg-ink text-white p-6 sm:p-8 lg:p-10">
                <div class="flex flex-wrap items-center gap-3 pb-5 border-b border-white/10 text-xs font-bold text-white/70">
                    <span class="badge-on-dark">استریم کوتاه‌مدت</span>
                    <span class="badge-on-dark">ثبت خودکار پیشرفت</span>
                    <span class="badge-on-dark">محیط اختصاصی دانشجو</span>
                </div>

                <template x-if="manifest">
                    <div class="pt-6"
                         x-transition:enter="transition duration-400 ease-out"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100">
                        <video x-ref="player" class="aspect-video w-full rounded-[1.5rem] bg-black" controls playsinline preload="metadata" x-bind:src="manifest" aria-label="پخش {{ $video->title }}"></video>

                        <div class="mt-5 flex flex-wrap items-center gap-4">
                            {{-- Transform-based fill (scaleX from the right in RTL):
                                 animating width would reflow on every update. --}}
                            <div class="h-2 w-44 overflow-hidden rounded-full bg-cream/20" role="progressbar" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progressPercent">
                                <div class="h-2 w-full origin-right rounded-full bg-surface-soft transition-transform duration-500 ease-out" x-bind:style="`transform:scaleX(${progressPercent / 100})`"></div>
                            </div>
                            <p class="text-sm text-white/70"><span x-text="progressPercent">0</span>٪ مشاهده شده</p>
                            <p x-show="completed" x-cloak class="text-sm font-bold text-white">این ویدیو برای شما تکمیل شده است.</p>
                        </div>

                        <div class="mt-5 rounded-2xl border border-white/10 bg-white/5 p-4 text-xs leading-6 text-white/65">
                            نشانی پخش به‌صورت کوتاه‌مدت ایجاد می‌شود و فقط برای دسترسی همین حساب معتبر است. اگر پخش انجام نشد، یک‌بار صفحه را تازه‌سازی کنید یا دوباره مجوز پخش بگیرید.
                        </div>
                    </div>
                </template>

                <template x-if="!manifest">
                    <div class="flex min-h-[24rem] flex-col items-center justify-center text-center">
                        <span class="icon-frame-ghost icon-frame-xl icon-frame-round">
                            <x-ui.icon name="play" class="size-6" />
                        </span>
                        <h2 class="mt-6 text-2xl font-black text-white">پخش ویدیو آماده است</h2>
                        @if ($canPlay)
                            <p class="mt-3 max-w-md text-sm leading-7 text-white/70">برای دریافت مجوز کوتاه‌مدت پخش، دکمه زیر را انتخاب کنید. پیشرفت شما هنگام مشاهده ذخیره خواهد شد.</p>
                            <button type="button" x-on:click="loadPlayback" x-bind:disabled="loading" class="button-primary mt-6 disabled:cursor-wait disabled:opacity-60">
                                <x-ui.icon name="play" class="size-4" />
                                <span x-show="!loading" x-transition.opacity.duration.150ms>دریافت مجوز پخش</span>
                                <span x-show="loading" x-cloak x-transition.opacity.duration.150ms>در حال آماده‌سازی پخش…</span>
                            </button>
                        @else
                            <p class="mt-3 max-w-md text-sm leading-7 text-white/70">این ویدیو برای حساب شما فعال نیست. ابتدا ثبت‌نام یا اشتراک لازم را تکمیل کنید.</p>
                            <a href="{{ route('courses.show', $course) }}" class="button-primary mt-6">
                                <x-ui.icon name="graduation" class="size-4" />
                                مشاهده جزئیات دوره
                            </a>
                        @endif
                        <p x-show="error" x-cloak class="mt-5 text-sm font-bold text-rausch-text" role="alert" x-text="error"></p>
                    </div>
                </template>
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft space-y-4">
                <h2 class="text-sm font-extrabold text-ink">شناسنامه آموزشی ویدیو</h2>
                <dl class="grid gap-3 text-xs leading-6 text-muted">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">نویسنده / مدرس</dt>
                        <dd class="text-left">{{ $video->author?->name ?: 'تیم آموزشی بروکا' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">بازبین علمی</dt>
                        <dd class="text-left">{{ $video->reviewer?->name ?: 'در حال ثبت توسط تیم علمی' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">مدت ویدیو</dt>
                        <dd class="text-left">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) : 'در حال تکمیل' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">آستانه تکمیل</dt>
                        <dd class="text-left">{{ $threshold }}٪ مشاهده</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">تاریخ انتشار</dt>
                        <dd class="text-left">{{ $video->published_at?->timezone($displayTimezone)?->format('Y/m/d') ?: 'منتشرشده برای دانشجویان مجاز' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-bold text-ink">آخرین به‌روزرسانی</dt>
                        <dd class="text-left">{{ $video->updated_at?->timezone($displayTimezone)?->format('Y/m/d H:i') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="editorial-card is-soft">
                <h2 class="text-base font-extrabold text-ink">نکات دسترسی</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <strong>دسترسی کنترل‌شده</strong>
                            <span>نشانی ویدیو عمومی نیست و تنها پس از تأیید دسترسی همین کاربر صادر می‌شود.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="chart" class="size-5" /></span>
                        <div>
                            <strong>ثبت پیشرفت</strong>
                            <span>هنگام تماشا، درصد مشاهده ذخیره می‌شود تا مسیر یادگیری و تکمیل درس‌ها شفاف بماند.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>

@push('scripts')
<script>
    function videoPlayback(initialPercent = 0, initiallyCompleted = false) {
        return {
            loading: false,
            manifest: null,
            error: '',
            progressPercent: initialPercent,
            completed: initiallyCompleted,
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
                        throw new Error('این ویدیو در حال حاضر برای حساب شما در دسترس نیست.');
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
