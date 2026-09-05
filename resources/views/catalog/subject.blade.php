@extends('layouts.app')

@section('title', 'دوره‌های ' . $subject->name . ' — ' . __('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit($subject->description ?: ('مشاهده دوره‌های ' . $subject->name . ' در بروکا همراه با مدرس، بازبین علمی، ویدیو، جزوه، فلش‌کارت و آزمون.'), 155))
@section('canonical', route('subjects.show', $subject))

@section('content')
<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-8 section-intro">
            {{-- Visible breadcrumbs: mirror of the BreadcrumbList JSON-LD. --}}
            <nav aria-label="مسیر صفحه" class="flex items-center gap-2 text-[11px] font-bold text-muted">
                <a href="{{ route('home') }}" class="hover:text-rausch transition-colors">خانه</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('catalog') }}" class="hover:text-rausch transition-colors">کاتالوگ</a>
                <span aria-hidden="true">/</span>
                <span class="text-ink">{{ $subject->name }}</span>
            </nav>
            <h1 class="section-title mt-4">دوره‌های {{ $subject->name }}</h1>
            <p class="section-copy max-w-3xl">{{ $subject->description ?: 'این مبحث مجموعه‌ای از دوره‌های ساخت‌یافته، ویدیوهای آموزشی، جزوات و ابزارهای مرور را در یک مسیر روشن گرد هم می‌آورد.' }}</p>
        </div>
        <div class="lg:col-span-4">
            <div class="trust-banner">
                <div class="flex items-start gap-3">
                    <span class="icon-frame"><x-ui.icon name="book" class="size-5" /></span>
                    <div>
                        <p class="text-sm font-extrabold text-ink">دروس این شاخه با همان استاندارد بروکا</p>
                        <p class="mt-2 text-xs leading-7 text-muted">هر دوره با نمایش مدرس، بازبین علمی، وضعیت انتشار و ابزارهای یادگیری مکمل معرفی می‌شود.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            <article class="course-card" data-reveal>
                @if ($course->cover_image_path)
                    <img src="{{ $course->cover_image_path }}" alt="{{ $course->title }}" class="aspect-[16/10] w-full object-cover">
                @else
                    <div class="aspect-[16/10] w-full bg-surface-soft border-b border-hairline-soft flex items-center justify-center">
                        <span class="icon-frame-soft icon-frame-xl icon-frame-round"><x-ui.icon name="graduation" class="size-6" /></span>
                    </div>
                @endif

                <div class="course-card__body">
                    <div class="course-card__meta">
                        <span class="badge-soft">{{ $subject->name }}</span>
                        <span class="badge-neutral">{{ $course->level ?: 'علوم پایه پزشکی' }}</span>
                    </div>

                    <div>
                        <h2 class="text-lg font-extrabold text-ink leading-7">
                            <a href="{{ route('courses.show', $course) }}" class="hover:text-rausch transition-colors">{{ $course->title }}</a>
                        </h2>
                        <p class="mt-3 text-sm leading-7 text-muted">{{ $course->excerpt ?: $course->description }}</p>
                    </div>

                    <div class="rounded-2xl bg-surface-soft p-3.5 space-y-2 text-xs">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-ink">مدرس / نویسنده</span>
                            <span class="text-muted">{{ $course->author->name ?? 'هیئت علمی بروکا' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold text-ink">بازبین علمی</span>
                            <span class="text-muted">{{ $course->reviewer->name ?? 'متخصص ناظر' }}</span>
                        </div>
                    </div>

                    <div class="course-card__stats">
                        <div>
                            <strong>{{ number_format($course->published_videos_count) }} درس</strong>
                            <span>ویدیو</span>
                        </div>
                        <div>
                            <strong>{{ number_format($course->published_notes_count) }} جزوه</strong>
                            <span>PDF</span>
                        </div>
                        <div>
                            <strong>{{ number_format($course->published_decks_count) }} دِک</strong>
                            <span>فلش‌کارت</span>
                        </div>
                        <div>
                            <strong>{{ number_format($course->published_quizzes_count) }} آزمون</strong>
                            <span>سنجش</span>
                        </div>
                    </div>

                    <a href="{{ route('courses.show', $course) }}" class="button-soft mt-1">مشاهده صفحه دوره ←</a>
                </div>
            </article>
        @empty
            <div class="col-span-3 empty-state">
                <h2 class="empty-state-title">هنوز دوره‌ای در این مبحث منتشر نشده است</h2>
                <p class="empty-state-copy">می‌توانید سایر موضوعات آموزشی را در کاتالوگ بروکا بررسی کنید یا بعداً دوباره به این صفحه سر بزنید.</p>
                <a href="{{ route('catalog') }}" class="button-secondary mt-5">مشاهده سایر شاخه‌ها</a>
            </div>
        @endforelse
    </div>

    <div class="mt-10">{{ $courses->links() }}</div>
</section>

@push('scripts')
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'دوره‌های ' . $subject->name,
        'description' => $subject->description ?: ('مشاهده دوره‌های ' . $subject->name . ' در بروکا.'),
        'url' => route('subjects.show', $subject),
        'breadcrumb' => [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('app.name'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'کاتالوگ', 'item' => route('catalog')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $subject->name, 'item' => route('subjects.show', $subject)],
            ],
        ],
        'inLanguage' => 'fa-IR',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'دوره‌های ' . $subject->name,
        'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems' => $courses->count(),
        // Full Course entities per ListItem (Course List rich result shape).
        'itemListElement' => $courses->values()->map(fn ($course, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('courses.show', $course),
            'item' => array_filter([
                '@type' => 'Course',
                'name' => $course->title,
                'description' => $course->excerpt ?: $course->description,
                'url' => route('courses.show', $course),
                'inLanguage' => 'fa-IR',
                'provider' => ['@type' => 'Organization', 'name' => 'Broca', 'alternateName' => 'بروکا', 'url' => url('/')],
                'author' => $course->author ? ['@type' => 'Person', 'name' => $course->author->name] : null,
            ], fn ($value) => $value !== null && $value !== ''),
        ])->all(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endpush
@endsection
