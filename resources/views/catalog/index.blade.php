@extends('layouts.app')

@php
    $catalogTitle = $selectedSubject
        ? ('دوره‌های ' . $selectedSubject->name . ' — ' . __('app.name'))
        : ('کاتالوگ جامع دوره‌های علوم پزشکی — ' . __('app.name'));
    $catalogDescription = $selectedSubject
        ? \Illuminate\Support\Str::limit($selectedSubject->description ?: ('مشاهده دوره‌های ' . $selectedSubject->name . ' در بروکا با نمایش مدرس، بازبین علمی، ویدیو، جزوه، فلش‌کارت و آزمون.'), 155)
        : 'کاتالوگ دوره‌های پزشکی بروکا با نمایش مدرس، بازبین علمی، جزوات، فلش‌کارت‌ها، آزمون‌ها و مسیر یادگیری قابل پیگیری.';
    $catalogCanonical = $selectedSubject ? route('subjects.show', $selectedSubject) : route('catalog');
    $catalogRobots = request()->filled('q') || request()->filled('subject') ? 'noindex, follow' : 'index, follow';
@endphp

@section('title', $catalogTitle)
@section('meta_description', $catalogDescription)
@section('canonical', $catalogCanonical)
@section('robots', $catalogRobots)

@section('content')
<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-7 section-intro">
            <span class="eyebrow">کتابخانه آموزشی بروکا</span>
            <h1 class="section-title mt-4">دوره‌های پزشکی با ساختار روشن، بازبینی علمی و مسیر یادگیری قابل پیگیری</h1>
            <p class="section-copy max-w-3xl">هر دوره در بروکا فقط یک معرفی کوتاه نیست؛ مجموعه‌ای از ویدیو، جزوه، فلش‌کارت و ارزیابی است که در یک تجربه فارسی، منظم و قابل اعتماد به هم متصل شده‌اند.</p>
        </div>

        <div class="lg:col-span-5">
            <div class="trust-banner">
                <div class="flex items-start gap-3">
                    <span class="icon-frame"><x-ui.icon name="shield" class="size-5" /></span>
                    <div>
                        <p class="text-sm font-extrabold text-ink">آنچه اینجا می‌بینید صرفاً فهرست دوره نیست</p>
                        <p class="mt-2 text-xs leading-7 text-muted">برای هر دوره، نویسنده یا مدرس، بازبین علمی، وضعیت انتشار و ابزارهای مکمل یادگیری در کنار هم نمایش داده می‌شود تا تصمیم‌گیری ساده‌تر و اعتمادپذیرتر باشد.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($selectedSubject || request()->filled('q'))
        <div class="meta-card is-soft mt-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-ink">نمای فعلی کاتالوگ</p>
                    <p class="mt-2 text-sm leading-7 text-muted">
                        @if ($selectedSubject && request()->filled('q'))
                            نتایج جستجو برای «{{ request('q') }}» در مبحث «{{ $selectedSubject->name }}» نمایش داده می‌شود.
                        @elseif ($selectedSubject)
                            در حال مرور نسخه فیلترشده مبحث «{{ $selectedSubject->name }}» هستید. نسخه مرجع و قابل نمایه‌سازی این موضوع در صفحه اختصاصی آن قرار دارد.
                        @else
                            نتایج جستجو برای «{{ request('q') }}» نمایش داده می‌شود. برای مرور کامل ساختار سایت، می‌توانید به نمای اصلی کاتالوگ برگردید.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($selectedSubject)
                        <a href="{{ route('subjects.show', $selectedSubject) }}" class="button-soft">صفحه اختصاصی مبحث</a>
                    @endif
                    <a href="{{ route('catalog') }}" class="button-secondary">بازگشت به کاتالوگ اصلی</a>
                </div>
            </div>
        </div>
    @endif

    <div class="surface-panel mt-10 p-4 sm:p-5 rounded-[1.75rem]">
        <form method="get" action="{{ route('catalog') }}" class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between" role="search">
            <div class="flex-1 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <label for="q" class="block text-[11px] font-bold text-muted mb-1.5">جستجو در عنوان دوره</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="مثلاً الکتروفیزیولوژی، نوروآناتومی، قفسه سینه..."
                           class="w-full rounded-full border border-ink/15 bg-white px-5 py-3 text-xs font-medium focus:border-ink transition-all" />
                </div>
                <div class="sm:min-w-[210px]">
                    <label for="subject-select" class="block text-[11px] font-bold text-muted mb-1.5">انتخاب مبحث</label>
                    <select id="subject-select" name="subject" class="w-full rounded-full border border-ink/15 bg-white px-4 py-3 text-xs font-bold">
                        <option value="">همه درس‌نامه‌ها</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->slug }}" @selected(request('subject') === $subject->slug)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-2 sm:justify-end">
                <button class="button-primary">
                    <x-ui.icon name="book" class="size-4" />
                    نمایش دوره‌ها
                </button>
                @if (request()->filled('q') || request()->filled('subject'))
                    <a href="{{ route('catalog') }}" class="button-secondary">پاک کردن فیلترها</a>
                @endif
            </div>
        </form>
    </div>

    <nav class="mt-8 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none" aria-label="مرور مباحث کاتالوگ">
        <a href="{{ route('catalog', array_filter(['q' => request('q')])) }}"
           class="{{ request()->missing('subject') ? 'inline-flex items-center gap-2 rounded-full border border-ink bg-ink px-3 py-1.5 text-[11px] font-bold whitespace-nowrap text-white' : 'badge-outline whitespace-nowrap' }}">
            همه مباحث
        </a>
        @foreach ($subjects as $subject)
            @php($subjectBrowseUrl = request()->filled('q') ? route('catalog', array_filter(['subject' => $subject->slug, 'q' => request('q')])) : route('subjects.show', $subject))
            <a href="{{ $subjectBrowseUrl }}"
               class="{{ request('subject') === $subject->slug ? 'inline-flex items-center gap-2 rounded-full border border-ink bg-ink px-3 py-1.5 text-[11px] font-bold whitespace-nowrap text-white' : 'badge-outline whitespace-nowrap' }}">
                {{ $subject->name }}
            </a>
        @endforeach
    </nav>

    @if ($courses->isEmpty())
        <div class="empty-state mt-12">
            <span class="icon-frame mx-auto mb-4">
                <x-ui.icon name="book" class="size-5" />
            </span>
            <h2 class="empty-state-title">دوره‌ای با این مشخصات پیدا نشد</h2>
            <p class="empty-state-copy">فیلترها را ساده‌تر کنید یا با حذف کلیدواژه‌ها، همه دوره‌های منتشرشده را دوباره مرور کنید.</p>
            <a href="{{ route('catalog') }}" class="button-secondary mt-5">مشاهده همه دوره‌ها</a>
        </div>
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $course)
                <article class="course-card">
                    @if ($course->cover_image_path)
                        <img src="{{ $course->cover_image_path }}" alt="{{ $course->title }}" class="aspect-[16/10] w-full object-cover">
                    @else
                        <div class="aspect-[16/10] w-full bg-surface-soft border-b border-hairline-soft flex items-center justify-center">
                            <span class="icon-frame-soft icon-frame-xl icon-frame-round">
                                <x-ui.icon name="graduation" class="size-6" />
                            </span>
                        </div>
                    @endif

                    <div class="course-card__body">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-2">
                                <div class="course-card__meta">
                                    <span class="badge-soft">{{ $course->subject->name ?? 'عمومی' }}</span>
                                    <span class="badge-neutral">{{ $course->level ?: 'علوم پایه پزشکی' }}</span>
                                </div>
                                <h2 class="text-lg font-extrabold leading-7 text-ink">
                                    <a href="{{ route('courses.show', $course) }}" class="hover:text-rausch transition-colors">{{ $course->title }}</a>
                                </h2>
                            </div>
                            @if ($course->published_at)
                                <span class="text-[11px] font-bold text-muted whitespace-nowrap" dir="ltr">{{ $course->published_at->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</span>
                            @endif
                        </div>

                        <p class="text-sm leading-7 text-muted">{{ $course->excerpt ?: $course->description }}</p>

                        <div class="rounded-2xl bg-surface-soft p-3.5 space-y-2">
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="font-bold text-ink">مدرس / نویسنده</span>
                                <span class="text-muted">{{ $course->author->name ?? 'هیئت علمی بروکا' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="font-bold text-ink">بازبین علمی</span>
                                <span class="text-muted">{{ $course->reviewer->name ?? 'متخصص ناظر' }}</span>
                            </div>
                        </div>

                        <div class="course-card__stats">
                            <div>
                                <strong>{{ number_format($course->published_videos_count) }} درس</strong>
                                <span>ویدیوهای منتشرشده</span>
                            </div>
                            <div>
                                <strong>{{ number_format($course->published_notes_count) }} جزوه</strong>
                                <span>فایل‌های PDF</span>
                            </div>
                            <div>
                                <strong>{{ number_format($course->published_decks_count) }} دِک</strong>
                                <span>مرور فاصله‌دار</span>
                            </div>
                            <div>
                                <strong>{{ number_format($course->published_quizzes_count) }} آزمون</strong>
                                <span>ارزیابی یادگیری</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <span class="text-xs font-bold text-teal">نمونه‌های رایگان برای ارزیابی کیفیت فعال هستند</span>
                            <a href="{{ route('courses.show', $course) }}" class="button-soft">مشاهده صفحه دوره ←</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10">{{ $courses->links() }}</div>
    @endif
</section>

@push('scripts')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $catalogTitle,
        'description' => $catalogDescription,
        'url' => $catalogCanonical,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => route('home'),
        ],
        'inLanguage' => 'fa-IR',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('app.name'), 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'کاتالوگ', 'item' => route('catalog')],
            $selectedSubject ? ['@type' => 'ListItem', 'position' => 3, 'name' => $selectedSubject->name, 'item' => route('subjects.show', $selectedSubject)] : null,
        ])),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $selectedSubject ? ('دوره‌های ' . $selectedSubject->name) : 'دوره‌های پزشکی بروکا',
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems' => $courses->count(),
        'itemListElement' => $courses->values()->map(fn ($course, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('courses.show', $course),
            'name' => $course->title,
        ])->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endpush
@endsection
