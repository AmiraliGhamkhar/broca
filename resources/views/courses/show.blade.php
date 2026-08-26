@extends('layouts.app')

@section('title', $course->title . ' — ' . __('app.name'))

@section('canonical', route('courses.show', $course))

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="text-sm font-black text-coral">{{ $course->subject->name }}</p>
    <h1 class="mt-4 max-w-4xl text-5xl font-black leading-tight sm:text-7xl">{{ $course->title }}</h1>
    <p class="mt-6 max-w-2xl text-lg leading-9 text-ink/65">{{ $course->description ?: $course->excerpt }}</p>

    <div class="mt-8 flex flex-wrap gap-3 text-sm font-bold">
        <span class="rounded-full bg-sun px-4 py-2">نویسنده: {{ $course->author?->name ?: '[PLACEHOLDER: نویسنده]' }} · {{ $course->author?->credentials ?: '[PLACEHOLDER: مدرک نویسنده]' }}</span>
        <span class="rounded-full bg-teal px-4 py-2">بازبینی: {{ $course->reviewer?->name ?: '[PLACEHOLDER: بازبین پزشکی]' }} · {{ $course->reviewer?->credentials ?: '[PLACEHOLDER: مدرک بازبین]' }}</span>
    </div>

    @guest
        <a href="{{ route('register') }}" class="mt-10 inline-flex rounded-full bg-ink px-7 py-4 font-black text-cream">برای ثبت‌نام وارد شو</a>
    @endguest

    @if ($isEnrolled)
        <p class="mt-10 inline-flex rounded-full bg-teal px-7 py-4 font-black text-cream">در این دوره ثبت‌نام کرده‌ای ✓</p>
    @else
        <form method="post" action="{{ route('courses.enroll', $course) }}" class="mt-10">
            @csrf
            <button class="rounded-full bg-ink px-7 py-4 font-black text-cream">ثبت‌نام رایگان در دوره</button>
        </form>
    @endif

    <div class="mt-20 grid gap-5 md:grid-cols-2">
        <div class="rounded-[2rem] border border-ink/15 p-6">
            <p class="text-sm font-black text-coral">ویدیوها</p>
            <ul class="mt-6 space-y-3">
                @forelse ($course->videos as $video)
                    <li class="rounded-xl bg-ink/5 p-4 font-bold">
                        <a href="{{ route('videos.show', [$course, $video]) }}" class="flex items-center justify-between gap-3 hover:text-coral focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">
                            <span>{{ $video->title }}</span>
                            <span class="flex items-center gap-2">
                                @if ($video->is_free_designated)<span class="rounded-full bg-teal px-3 py-1 text-xs text-cream">رایگان</span>@endif
                                <span aria-hidden="true">←</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="text-ink/60">هنوز ویدیویی منتشر نشده است.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-[2rem] border border-ink/15 p-6">
            <p class="text-sm font-black text-plum">جزوه‌ها</p>
            <ul class="mt-6 space-y-3">
                @forelse ($course->notes as $note)
                    <li class="rounded-xl bg-ink/5 p-4 font-bold">
                        <a href="{{ route('notes.show', [$course, $note]) }}" class="flex items-center justify-between gap-3 hover:text-coral focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">
                            <span>{{ $note->title }}</span>
                            <span class="flex items-center gap-2">
                                @if ($note->is_free_designated)<span class="rounded-full bg-teal px-3 py-1 text-xs text-cream">رایگان</span>@endif
                                <span aria-hidden="true">←</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="text-ink/60">هنوز جزوه‌ای منتشر نشده است.</li>
                @endforelse
            </ul>

            <p class="mt-8 text-sm text-ink/60">
                @foreach ($course->decks as $deck)<a href="{{ route('decks.study', [$course, $deck]) }}" class="me-2 underline hover:text-coral">{{ $deck->title }}</a>@endforeach
                @foreach ($course->quizzes as $quiz)<a href="{{ route('quizzes.show', $quiz) }}" class="me-2 underline hover:text-coral">{{ $quiz->title }}</a>@endforeach
            </p>
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
