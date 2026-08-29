@extends('layouts.app')

@section('title', $course->title . ' — ' . __('app.name'))

@section('canonical', route('courses.show', $course))

@section('content')
<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="space-y-4">
        <a href="{{ route('catalog') }}" class="text-xs font-bold text-rausch hover:underline">
            ← بازگشت به کاتالوگ دوره‌ها
        </a>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-rausch">{{ $course->subject->name ?? 'عمومی' }}</span>
            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold bg-surface-soft text-ink">
                {{ $course->level ?: 'علوم پایه پزشکی' }}
            </span>
        </div>
        <h1 class="font-display text-3xl sm:text-5xl text-ink leading-tight">{{ $course->title }}</h1>
        <p class="text-xs sm:text-sm text-muted max-w-3xl leading-7">
            {{ $course->description ?: $course->excerpt }}
        </p>

        <!-- Faculty Byline Pills -->
        <div class="flex flex-wrap items-center gap-3 pt-2 text-xs font-bold">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white border border-hairline-soft px-4 py-2 text-ink shadow-float">
                <span>👨‍⚕️ استاد:</span>
                <span class="text-rausch">{{ $course->author?->name ?: 'هیئت علمی بروکا' }}</span>
                <span class="text-[11px] text-muted font-normal">({{ $course->author?->credentials ?: 'متخصص بالینی' }})</span>
            </span>

            <span class="inline-flex items-center gap-1.5 rounded-full bg-teal/10 border border-teal/20 px-4 py-2 text-teal shadow-float">
                <span>✓ بازبین علمی:</span>
                <span>{{ $course->reviewer?->name ?: 'متخصص ناظر' }}</span>
                <span class="text-[11px] font-normal">({{ $course->reviewer?->credentials ?: 'هیئت علمی' }})</span>
            </span>
        </div>

        <!-- Enrollment Call to Action -->
        <div class="pt-4">
            @guest
                <a href="{{ route('register') }}" class="inline-block rounded-full bg-rausch px-8 py-4 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                    برای ثبت‌نام و دسترسی رایگان وارد شوید ←
                </a>
            @else
                @if ($isEnrolled)
                    <div class="inline-flex items-center gap-2 rounded-full bg-teal/10 border border-teal/30 px-6 py-3 font-bold text-teal text-xs">
                        <span>✓</span>
                        <span>شما در این دوره ثبت‌نام کرده‌اید. دسترسی به دروس فعال است.</span>
                    </div>
                @else
                    <form method="post" action="{{ route('courses.enroll', $course) }}" class="inline-block">
                        @csrf
                        <button type="submit" class="rounded-full bg-rausch px-8 py-4 font-bold text-white text-xs hover:bg-rausch-active transition-colors shadow-float">
                            ثبت‌نام رایگان در این دوره ←
                        </button>
                    </form>
                @endif
            @endguest
        </div>
    </div>

    <!-- Course Content Grid -->
    <div class="mt-14 grid gap-8 lg:grid-cols-2">

        <!-- Module 1: Video Lessons -->
        <div class="surface-panel p-6 sm:p-8 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-hairline-soft">
                <h2 class="text-base font-bold text-ink flex items-center gap-2">
                    <span>🎥</span> ویدیوهای آموزشی دوره
                </h2>
                <span class="text-xs text-muted font-bold">{{ $course->videos->count() }} درس</span>
            </div>

            <ul class="space-y-3 text-xs">
                @forelse ($course->videos as $idx => $video)
                    <li class="p-3.5 rounded-xl bg-white border border-hairline-soft hover:border-hairline transition-all">
                        <a href="{{ route('videos.show', [$course, $video]) }}" class="flex items-center justify-between font-bold text-ink">
                            <div class="flex items-center gap-3">
                                <span class="grid size-7 place-items-center rounded-full bg-surface-soft text-[11px] font-bold text-ink">
                                    {{ $idx + 1 }}
                                </span>
                                <div>
                                    <span class="block font-bold text-sm text-ink">{{ $video->title }}</span>
                                    <span class="text-[11px] text-muted font-normal">{{ $video->duration_seconds ? gmdate('i:s', $video->duration_seconds) . ' دقیقه' : 'درس ویدیویی' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($video->is_free_designated)
                                    <span class="rounded-full bg-teal/10 text-teal px-2.5 py-0.5 text-[11px] font-bold">رایگان</span>
                                @else
                                    <span class="rounded-full bg-surface-strong text-muted px-2.5 py-0.5 text-[11px] font-bold">ویژه</span>
                                @endif
                                <span class="text-rausch">←</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="text-muted py-4 text-center">هنوز ویدیویی در این دوره منتشر نشده است.</li>
                @endforelse
            </ul>
        </div>

        <!-- Module 2: Notes & Handouts -->
        <div class="surface-panel p-6 sm:p-8 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-hairline-soft">
                <h2 class="text-base font-bold text-ink flex items-center gap-2">
                    <span>📄</span> جزوات و خلاصه دروس (PDF)
                </h2>
                <span class="text-xs text-muted font-bold">{{ $course->notes->count() }} جزوه</span>
            </div>

            <ul class="space-y-3 text-xs">
                @forelse ($course->notes as $note)
                    <li class="p-3.5 rounded-xl bg-white border border-hairline-soft hover:border-hairline transition-all">
                        <a href="{{ route('notes.show', [$course, $note]) }}" class="flex items-center justify-between font-bold text-ink">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">📑</span>
                                <div>
                                    <span class="block font-bold text-sm text-ink">{{ $note->title }}</span>
                                    <span class="text-[11px] text-muted font-normal">فرمت PDF اختصاصی</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($note->is_free_designated)
                                    <span class="rounded-full bg-teal/10 text-teal px-2.5 py-0.5 text-[11px] font-bold">رایگان</span>
                                @else
                                    <span class="rounded-full bg-surface-strong text-muted px-2.5 py-0.5 text-[11px] font-bold">ویژه</span>
                                @endif
                                <span class="text-rausch">دانلود ←</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="text-muted py-4 text-center">هنوز جزوه‌ای در این دوره منتشر نشده است.</li>
                @endforelse
            </ul>

            <!-- Decks and Quizzes Quick Links inside the course -->
            @if ($course->decks->isNotEmpty() || $course->quizzes->isNotEmpty())
                <div class="pt-6 border-t border-hairline-soft space-y-3">
                    <h3 class="text-xs font-bold text-ink">کارت‌های مرور و آزمون‌های این دوره:</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($course->decks as $deck)
                            <a href="{{ route('decks.study', [$course, $deck]) }}"
                               class="px-4 py-2 rounded-full bg-surface-soft text-ink text-xs font-bold hover:bg-white border border-hairline-soft transition-all">
                                🗂 {{ $deck->title }}
                            </a>
                        @endforeach
                        @foreach ($course->quizzes as $quiz)
                            <a href="{{ route('quizzes.show', $quiz) }}"
                               class="px-4 py-2 rounded-full bg-ink text-white text-xs font-bold hover:bg-rausch transition-colors">
                                📝 {{ $quiz->title }}
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
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $course->title,
        'description' => $course->excerpt ?: $course->title,
        'inLanguage' => 'fa-IR',
        'provider' => ['@type' => 'Organization', 'name' => config('app.name'), 'sameAs' => url('/')],
        'author' => $course->author ? ['@type' => 'Person', 'name' => $course->author->name, 'jobTitle' => $course->author->credentials] : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush
@endsection
