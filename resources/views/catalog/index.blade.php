@extends('layouts.app')

@section('title', 'کاتالوگ جامع دوره‌های علوم پزشکی — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end pb-8 border-b border-hairline-soft">
        <div>
            <span class="text-xs font-bold text-rausch">کتابخانه و درس‌نامه‌های بروکا</span>
            <h1 class="mt-2 font-display text-3xl sm:text-5xl text-ink">دوره‌های آموزشی</h1>
            <p class="text-xs sm:text-sm text-muted mt-2 max-w-xl leading-6">
                مباحث علوم پایه پزشکی، فیزیولوژی، آناتومی و نورولوژی همراه با ویدیوها، جزوات PDF، فلش‌کارت‌های SM-2 و آزمون‌های تشخیصی.
            </p>
        </div>

        <!-- Search Bar -->
        <form method="get" action="{{ route('catalog') }}" class="flex items-center gap-2" role="search">
            @if (request('subject'))
                <input type="hidden" name="subject" value="{{ request('subject') }}" />
            @endif
            <input id="q" name="q" value="{{ request('q') }}" placeholder="جستجوی دوره..."
                   class="rounded-full border border-ink/20 bg-white/70 px-5 py-3 text-xs font-medium focus:bg-white focus:border-coral focus:ring-1 focus:ring-coral transition-all min-w-[220px]" />
            <button class="rounded-full bg-ink px-6 py-3 font-bold text-white text-xs hover:bg-rausch transition-colors">
                جستجو
            </button>
        </form>
    </div>

    <!-- Subject Category Filter Pills (Airbnb style) -->
    <nav class="mt-8 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none" aria-label="فیلتر بر اساس درس‌نامه">
        <a href="{{ route('catalog', array_filter(['q' => request('q')])) }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ request()->missing('subject') ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft border border-hairline-soft' }}">
            همه مباحث
        </a>
        @foreach ($subjects as $subject)
            <a href="{{ route('catalog', array_filter(['subject' => $subject->slug, 'q' => request('q')])) }}"
               class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ request('subject') === $subject->slug ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft border border-hairline-soft' }}">
                {{ $subject->name }}
            </a>
        @endforeach
    </nav>

    @if ($courses->isEmpty())
        <div class="mt-12 rounded-3xl border border-dashed border-hairline-soft bg-white/40 p-12 text-center space-y-3">
            <span class="text-4xl block">🔍</span>
            <h2 class="text-xl font-bold text-ink">دوره‌ای با این مشخصات یافت نشد</h2>
            <p class="text-xs text-muted max-w-sm mx-auto">می‌توانید فیلترها را پاک کنید یا عبارت دیگری را جستجو نمایید.</p>
            <a href="{{ route('catalog') }}" class="inline-block mt-2 font-bold text-xs text-rausch underline">مشاهده همه دوره‌ها</a>
        </div>
    @else
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                <div class="interactive-card surface-panel p-6 rounded-3xl flex flex-col justify-between hover:shadow-lg transition-all">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-rausch">{{ $course->subject->name ?? 'عمومی' }}</span>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold bg-surface-soft text-ink">
                                {{ $course->level ?: 'علوم پایه' }}
                            </span>
                        </div>

                        <h2 class="text-lg font-bold text-ink leading-7">
                            <a href="{{ route('courses.show', $course) }}" class="hover:text-rausch transition-colors">
                                {{ $course->title }}
                            </a>
                        </h2>

                        <p class="text-xs text-muted leading-6 line-clamp-3">
                            {{ $course->excerpt ?: $course->description }}
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-hairline-soft space-y-3">
                        <div class="flex items-center justify-between text-xs text-muted">
                            <span class="font-bold text-ink">استاد: {{ $course->author->name ?? 'هیئت علمی بروکا' }}</span>
                            <span>بازبین: {{ $course->reviewer->name ?? 'متخصص ناظر' }}</span>
                        </div>

                        <a href="{{ route('courses.show', $course) }}"
                           class="block w-full py-2.5 rounded-full bg-surface-soft hover:bg-ink hover:text-white text-center text-xs font-bold text-ink transition-colors">
                            ورود و سرفصل‌های دوره ←
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-10">{{ $courses->links() }}</div>
    @endif
</section>
@endsection
