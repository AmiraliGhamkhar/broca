@extends('layouts.app')

@section('title', ($post->meta_title ?: $post->title) . ' — ' . __('app.name'))
@section('meta_description', $post->meta_description ?: \Illuminate\Support\Str::limit($post->excerpt ?: $post->content, 155))
@section('meta_author', $post->reviewer_name ?: ($post->author_name ?: __('app.name')))
@section('canonical', route('blog.show', $post->slug))
@section('og_type', 'article')
@if ($post->published_at)
    @section('article_published_time', $post->published_at->toIso8601String())
@endif
@section('article_modified_time', $post->updated_at?->toIso8601String())

@section('content')
<article class="section-shell section-stack">
    <div class="reading-shell">
        <a href="{{ route('blog.index') }}" class="text-xs font-bold text-rausch hover:underline">← بازگشت به وبلاگ</a>

        <header class="mt-6 space-y-5">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                @if ($post->category)
                    <span class="badge-soft">{{ $post->category }}</span>
                @endif
                @if ($post->published_at)
                    <span class="badge-outline" dir="ltr">انتشار: {{ $post->published_at->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}</span>
                @endif
                <span class="badge-success">محتوای آموزشی و غیرتشخیصی</span>
            </div>

            <div class="space-y-4">
                <h1 class="section-title text-[clamp(2rem,4vw,3.5rem)]">{{ $post->title }}</h1>
                @if ($post->excerpt)
                    <p class="text-base leading-8 text-body font-medium max-w-3xl">{{ $post->excerpt }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="meta-card">
                    <div class="flex items-start gap-3">
                        <span class="icon-frame-soft"><x-ui.icon name="users" class="size-5" /></span>
                        <div>
                            <p class="text-[11px] font-bold text-muted">نویسنده</p>
                            <p class="mt-1 text-sm font-extrabold text-ink">{{ $post->author_name ?: 'تیم علمی بروکا' }}</p>
                            <p class="mt-1 text-xs text-muted leading-6">این بخش برای شفاف‌سازی منبع تولید محتوا و افزایش اعتماد مخاطب نمایش داده می‌شود.</p>
                        </div>
                    </div>
                </div>
                <div class="meta-card">
                    <div class="flex items-start gap-3">
                        <span class="icon-frame"><x-ui.icon name="shield" class="size-5" /></span>
                        <div>
                            <p class="text-[11px] font-bold text-muted">بازبین علمی</p>
                            <p class="mt-1 text-sm font-extrabold text-ink">{{ $post->reviewer_name ?: 'در مسیر تکمیل متادیتای مقاله' }}</p>
                            <p class="mt-1 text-xs text-muted leading-6">وجود بازبین علمی در محتوای پزشکی یکی از مهم‌ترین نشانه‌های مسئولیت‌پذیری و کیفیت است.</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @if ($post->cover_image_path)
            <img src="{{ $post->cover_image_path }}" alt="{{ $post->title }}" class="mt-8 w-full rounded-[2rem] object-cover max-h-[32rem] border border-hairline-soft shadow-float">
        @endif

        <div class="trust-banner mt-8">
            <div class="flex items-start gap-3">
                <span class="icon-frame"><x-ui.icon name="badge-check" class="size-5" /></span>
                <div>
                    <p class="text-sm font-extrabold text-ink">یادداشت آموزشی، نه توصیه درمانی</p>
                    <p class="mt-2 text-xs leading-7 text-muted">این مقاله برای یادگیری و آشنایی علمی تهیه شده است. در بروکا، نمایش نویسنده، بازبین و زمان انتشار بخشی از طراحی اعتمادمحور برای محتوای پزشکی است.</p>
                </div>
            </div>
        </div>

        <div class="reading-prose mt-10 whitespace-pre-line">
            {!! nl2br(e($post->content)) !!}
        </div>

        <div class="mt-12 grid gap-4 sm:grid-cols-3">
            <div class="stat-card">
                <small>شفافیت محتوا</small>
                <strong>نویسنده و بازبین مشخص</strong>
            </div>
            <div class="stat-card">
                <small>کاربرد مقاله</small>
                <strong>آموزشی و مرور مفهومی</strong>
            </div>
            <div class="stat-card">
                <small>گام بعدی</small>
                <strong>ورود به دوره‌های مرتبط</strong>
            </div>
        </div>

        <div class="mt-10 editorial-card is-soft">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-ink">بعد از این مقاله، مسیر یادگیری را در دوره‌ها ادامه دهید</h2>
                    <p class="mt-2 text-sm leading-7 text-muted">وبلاگ بروکا برای ایجاد زمینه، مرور و آشنایی طراحی شده است؛ عمق بیشتر یادگیری در دوره‌ها، جزوات، فلش‌کارت‌ها و آزمون‌ها اتفاق می‌افتد.</p>
                </div>
                <div class="flex flex-wrap gap-3 shrink-0">
                    <a href="{{ route('catalog') }}" class="button-primary">مشاهده دوره‌ها</a>
                    <a href="{{ route('plans') }}" class="button-secondary">بررسی پلن‌ها</a>
                </div>
            </div>
        </div>
    </div>
</article>

@push('scripts')
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 155)),
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => $post->author_name ? ['@type' => 'Person', 'name' => $post->author_name] : null,
        'contributor' => $post->reviewer_name ? [['@type' => 'Person', 'name' => $post->reviewer_name]] : null,
        'image' => $post->cover_image_path ? [url($post->cover_image_path)] : null,
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
        'mainEntityOfPage' => route('blog.show', $post->slug),
        'inLanguage' => 'fa-IR',
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endpush
@endsection
