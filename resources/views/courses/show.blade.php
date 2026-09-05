@extends('layouts.app')

@section('title', $course->title . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit($course->excerpt ?: $course->description ?: ('آشنایی با دوره «' . $course->title . '» در بروکا، همراه با ویدیوها، جزوات، فلش‌کارت‌ها و آزمون‌های آموزشی.'), 155))
@section('meta_author', $course->reviewer?->name ?: ($course->author?->name ?: __('app.name')))
@section('canonical', route('courses.show', $course))

@section('content')
<section class="section-shell section-stack">
    {{-- Visible breadcrumbs: mirror of the BreadcrumbList JSON-LD below. --}}
    <nav aria-label="مسیر صفحه" class="flex items-center gap-2 text-[11px] font-bold text-muted">
        <a href="{{ route('home') }}" class="hover:text-rausch transition-colors">خانه</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors">کاتالوگ</a>
        @if ($course->subject)
            <span aria-hidden="true">/</span>
            <a href="{{ route('subjects.show', $course->subject) }}" class="hover:text-rausch transition-colors">{{ $course->subject->name }}</a>
        @endif
        <span aria-hidden="true">/</span>
        <span class="text-ink">{{ $course->title }}</span>
    </nav>

    <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
        <div class="lg:col-span-7 space-y-5">
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge-soft">{{ $course->subject->name ?? 'عمومی' }}</span>
                <span class="badge-neutral">{{ $course->level ?: 'علوم پایه پزشکی' }}</span>
                @if ($course->published_at)
                    <span class="badge-outline" dir="ltr">انتشار: {{ $course->published_at->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</span>
                @endif
            </div>

            <div class="section-intro max-w-4xl">
                <h1 class="section-title">{{ $course->title }}</h1>
                <p class="section-copy max-w-3xl">{{ $course->description ?: $course->excerpt }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="meta-card">
                    <div class="flex items-start gap-3">
                        <span class="icon-frame"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <p class="text-[11px] font-bold text-muted">مدرس / نویسنده</p>
                            <p class="mt-1 text-sm font-extrabold text-ink">{{ $course->author?->name ?: 'هیئت علمی بروکا' }}</p>
                            <p class="mt-1 text-xs text-muted leading-6">{{ $course->author?->credentials ?: 'محتوای آموزشی با ساختار حرفه‌ای برای فراگیران علوم پزشکی.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="meta-card">
                    <div class="flex items-start gap-3">
                        <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <p class="text-[11px] font-bold text-muted">بازبین علمی</p>
                            <p class="mt-1 text-sm font-extrabold text-ink">{{ $course->reviewer?->name ?: 'متخصص ناظر' }}</p>
                            <p class="mt-1 text-xs text-muted leading-6">{{ $course->reviewer?->credentials ?: 'تأکید بر شفافیت علمی و انتشار مسئولانه محتوای پزشکی.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="trust-banner">
                <div class="flex items-start gap-3">
                    <span class="icon-frame"><x-ui.icon name="badge-check" class="size-5" /></span>
                    <div>
                        <p class="text-sm font-extrabold text-ink">این دوره بخشی از یک تجربه آموزشی چندلایه است</p>
                        <p class="mt-2 text-xs leading-7 text-muted">یادگیری در بروکا فقط به مشاهده ویدیو محدود نیست. این صفحه ویدیوها، جزوات، مرور فاصله‌دار و ارزیابی را در یک مسیر آموزشی واحد کنار هم قرار می‌دهد.</p>
                    </div>
                </div>
            </div>

            <div>
                @guest
                    <a href="{{ route('register') }}" class="button-primary">
                        <x-ui.icon name="graduation" class="size-4" />
                        ثبت‌نام و شروع استفاده رایگان
                    </a>
                @else
                    @if ($isEnrolled)
                        <div class="badge-success px-4 py-3 text-xs">
                            <x-ui.icon name="badge-check" class="size-4" />
                            شما در این دوره ثبت‌نام کرده‌اید و مسیر یادگیری فعال است.
                        </div>
                    @else
                        <form method="post" action="{{ route('courses.enroll', $course) }}" class="inline-block">
                            @csrf
                            <button type="submit" class="button-primary">
                                <x-ui.icon name="graduation" class="size-4" />
                                ثبت‌نام رایگان در این دوره
                            </button>
                        </form>
                    @endif
                @endguest
            </div>
        </div>

        <div class="lg:col-span-5 space-y-4">
            @if ($course->cover_image_path)
                <img src="{{ $course->cover_image_path }}" alt="{{ $course->title }}" class="w-full rounded-[2rem] object-cover max-h-[28rem] border border-hairline-soft shadow-float">
            @endif

            <div class="meta-card">
                <dl>
                    <div>
                        <dt>ویدیوهای منتشرشده</dt>
                        <dd>{{ number_format($course->videos->count()) }} درس</dd>
                    </div>
                    <div>
                        <dt>جزوات و فایل‌های PDF</dt>
                        <dd>{{ number_format($course->notes->count()) }} فایل</dd>
                    </div>
                    <div>
                        <dt>فلش‌کارت‌های مرور</dt>
                        <dd>{{ number_format($course->decks->count()) }} دِک</dd>
                    </div>
                    <div>
                        <dt>آزمون‌های دوره</dt>
                        <dd>{{ number_format($course->quizzes->count()) }} آزمون</dd>
                    </div>
                    <div>
                        <dt>تاریخ انتشار</dt>
                        <dd>{{ $course->published_at?->timezone(config('broca.display_timezone'))->format('Y/m/d') ?: 'در دسترس عمومی' }}</dd>
                    </div>
                    <div>
                        <dt>آخرین به‌روزرسانی</dt>
                        <dd>{{ $course->updated_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="meta-card is-soft">
                <p class="text-[11px] font-bold text-muted">بیانیه آموزشی</p>
                <p class="mt-2 text-sm leading-7 text-ink">این محتوا برای آموزش و ارتقای دانش فراگیران علوم پزشکی تهیه شده است و جایگزین تصمیم یا اقدام بالینی برای بیمار نیست.</p>
            </div>
        </div>
    </div>

    <div class="mt-14 grid gap-8 lg:grid-cols-2">
        <div class="editorial-card space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-hairline-soft">
                <h2 class="text-lg font-extrabold text-ink flex items-center gap-2">
                    <span class="icon-frame-soft icon-frame-sm"><x-ui.icon name="play" class="size-4" /></span>
                    ویدیوهای آموزشی دوره
                </h2>
                <span class="text-xs text-muted font-bold">{{ $course->videos->count() }} درس</span>
            </div>

            <ul class="space-y-3 text-xs">
                @forelse ($course->videos as $idx => $video)
                    <li class="list-row-card">
                        <a href="{{ route('videos.show', [$course, $video]) }}" class="flex items-center justify-between gap-4 font-bold text-ink">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="icon-frame-soft icon-frame-sm icon-frame-round text-[11px] font-extrabold">{{ $idx + 1 }}</span>
                                <div class="min-w-0">
                                    <span class="block text-sm font-extrabold text-ink truncate">{{ $video->title }}</span>
                                    <span class="text-[11px] text-muted font-normal">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) . ' دقیقه' : 'درس ویدیویی' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="{{ $video->is_free_available ? 'badge-success' : 'badge-neutral' }}">{{ $video->is_free_available ? 'رایگان' : 'ویژه' }}</span>
                                <span class="text-rausch">←</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="text-muted py-4 text-center">هنوز ویدیویی در این دوره منتشر نشده است.</li>
                @endforelse
            </ul>
        </div>

        <div class="editorial-card space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-hairline-soft">
                <h2 class="text-lg font-extrabold text-ink flex items-center gap-2">
                    <span class="icon-frame-soft icon-frame-sm"><x-ui.icon name="document" class="size-4" /></span>
                    جزوات و فایل‌های آموزشی
                </h2>
                <span class="text-xs text-muted font-bold">{{ $course->notes->count() }} جزوه</span>
            </div>

            <ul class="space-y-3 text-xs">
                @forelse ($course->notes as $note)
                    <li class="list-row-card">
                        <a href="{{ route('notes.show', [$course, $note]) }}" class="flex items-center justify-between gap-4 font-bold text-ink">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="icon-frame-soft icon-frame-sm"><x-ui.icon name="document" class="size-4" /></span>
                                <div class="min-w-0">
                                    <span class="block text-sm font-extrabold text-ink truncate">{{ $note->title }}</span>
                                    <span class="text-[11px] text-muted font-normal">فایل PDF برای مرور و جمع‌بندی</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="{{ $note->is_free_available ? 'badge-success' : 'badge-neutral' }}">{{ $note->is_free_available ? 'رایگان' : 'ویژه' }}</span>
                                <span class="text-rausch">دانلود ←</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="text-muted py-4 text-center">هنوز جزوه‌ای در این دوره منتشر نشده است.</li>
                @endforelse
            </ul>

            @if ($course->decks->isNotEmpty() || $course->quizzes->isNotEmpty())
                <div class="soft-divider pt-6 space-y-4">
                    <h3 class="text-sm font-extrabold text-ink">مرور و ارزیابی تکمیلی این دوره</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($course->decks as $deck)
                            <a href="{{ route('decks.study', [$course, $deck]) }}" class="button-soft">
                                <x-ui.icon name="stack" class="size-4" />
                                {{ $deck->title }}
                            </a>
                        @endforeach
                        @foreach ($course->quizzes as $quiz)
                            <a href="{{ route('quizzes.show', $quiz) }}" class="button-secondary">
                                <x-ui.icon name="quiz" class="size-4" />
                                {{ $quiz->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

@push('scripts')
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $course->title,
        'url' => route('courses.show', $course),
        'description' => $course->excerpt ?: $course->description ?: $course->title,
        'inLanguage' => 'fa-IR',
        'educationalLevel' => $course->level ?: 'پیش‌درسی و علوم پایه پزشکی',
        'provider' => ['@type' => 'Organization', 'name' => 'Broca', 'alternateName' => 'بروکا', 'url' => url('/')],
        'author' => $course->author ? ['@type' => 'Person', 'name' => $course->author->name, 'jobTitle' => $course->author->credentials] : null,
        'contributor' => $course->reviewer ? ['@type' => 'Person', 'name' => $course->reviewer->name, 'jobTitle' => $course->reviewer->credentials] : null,
        'datePublished' => $course->published_at?->toIso8601String(),
        'dateModified' => $course->updated_at?->toIso8601String(),
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('app.name'), 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'کاتالوگ', 'item' => route('catalog')],
            $course->subject ? ['@type' => 'ListItem', 'position' => 3, 'name' => $course->subject->name, 'item' => route('subjects.show', $course->subject)] : null,
            ['@type' => 'ListItem', 'position' => $course->subject ? 4 : 3, 'name' => $course->title, 'item' => route('courses.show', $course)],
        ])),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endpush
@endsection
