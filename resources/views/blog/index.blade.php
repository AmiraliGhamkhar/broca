@extends('layouts.app')

@section('title', 'مقالات و وبلاگ آموزش پزشکی — ' . __('app.name'))
@section('meta_description', 'فهرست مقالات آموزشی پزشکی بروکا با نمایش نویسنده، بازبین علمی و تاریخ انتشار برای تجربه‌ای قابل اعتماد و حرفه‌ای.')

@section('content')
<section class="section-shell section-stack">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-8 section-intro">
            <span class="eyebrow">دانشنامه و تحلیل‌های آموزشی</span>
            <h1 class="section-title mt-4">وبلاگ بروکا برای مرور مفهومی، تبیین علمی و هدایت به مسیر یادگیری عمیق‌تر</h1>
            <p class="section-copy max-w-3xl">این بخش برای محتوای آموزشی طراحی شده است؛ با تمرکز بر وضوح، خوانایی، نمایش نویسنده و بازبین علمی و اتصال طبیعی مقاله به دوره‌ها و برنامه یادگیری.</p>
        </div>

        <div class="lg:col-span-4">
            <div class="trust-banner">
                <div class="flex items-start gap-3">
                    <span class="icon-frame"><x-ui.icon name="shield" class="size-5" /></span>
                    <div>
                        <p class="text-sm font-extrabold text-ink">طراحی اعتمادمحور برای مقالات پزشکی</p>
                        <p class="mt-2 text-xs leading-7 text-muted">مخاطب باید بداند محتوا چه زمانی منتشر شده، چه کسی آن را نوشته و چه جایگاهی در مسیر آموزشی او دارد.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($posts->isEmpty())
        <div class="empty-state mt-12">
            <span class="icon-frame mx-auto mb-4"><x-ui.icon name="document" class="size-5" /></span>
            <h2 class="text-xl font-extrabold text-ink">هنوز مقاله‌ای منتشر نشده است</h2>
            <p class="mt-2 text-xs text-muted max-w-sm mx-auto leading-7">اولین مقاله را از پنل مدیریت یا ربات تلگرام بروکا منتشر کنید تا این بخش به دانشنامه آموزشی سایت تبدیل شود.</p>
        </div>
    @else
        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($posts as $post)
                <article class="course-card">
                    @if ($post->cover_image_path)
                        <img src="{{ $post->cover_image_path }}" alt="{{ $post->title }}" class="aspect-[16/10] w-full object-cover">
                    @else
                        <div class="aspect-[16/10] w-full bg-surface-soft border-b border-hairline-soft flex items-center justify-center">
                            <span class="icon-frame-soft icon-frame-xl icon-frame-round">
                                <x-ui.icon name="document" class="size-6" />
                            </span>
                        </div>
                    @endif

                    <div class="course-card__body">
                        <div class="course-card__meta">
                            @if ($post->category)
                                <span class="badge-soft">{{ $post->category }}</span>
                            @endif
                            @if ($post->published_at)
                                <span class="badge-outline" dir="ltr">{{ $post->published_at->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</span>
                            @endif
                        </div>

                        <div>
                            <h2 class="text-lg font-extrabold text-ink leading-7">{{ $post->title }}</h2>
                            <p class="mt-3 text-sm leading-7 text-muted">{{ $post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 180) }}</p>
                        </div>

                        <div class="rounded-2xl bg-surface-soft p-3.5 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-ink">نویسنده</span>
                                <span class="text-muted">{{ $post->author_name ?: 'تیم علمی بروکا' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold text-ink">بازبین علمی</span>
                                <span class="text-muted">{{ $post->reviewer_name ?: 'در حال تکمیل' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-bold text-teal">مطالعه آموزشی و غیرتشخیصی</span>
                            <a href="{{ route('blog.show', $post->slug) }}" class="button-soft">مطالعه مقاله ←</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
