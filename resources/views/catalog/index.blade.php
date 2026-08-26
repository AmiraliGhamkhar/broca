@extends('layouts.app')

@section('title', 'دوره‌ها — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <div class="flex flex-col justify-between gap-8 sm:flex-row sm:items-end">
        <div>
            <p class="section-label">کتابخانهٔ بروکا</p>
            <h1 class="mt-4 text-5xl font-black sm:text-7xl">دوره‌ها</h1>
        </div>
        <form method="get" class="flex gap-2" role="search">
            @if (request('subject'))<input type="hidden" name="subject" value="{{ request('subject') }}" />@endif
            <label class="sr-only" for="q">جست‌وجوی دوره</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="جست‌وجوی دوره" class="rounded-full border border-ink/20 bg-transparent px-5 py-3" />
            <button class="rounded-full bg-ink px-5 py-3 font-black text-cream">جست‌وجو</button>
        </form>
    </div>

    <nav class="mt-10 flex flex-wrap gap-2" aria-label="پالایش بر اساس درس‌نامه">
        <a href="{{ route('catalog', array_filter(['q' => request('q')])) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request()->missing('subject') ? 'bg-ink text-cream' : 'border border-ink/20' }}">همه</a>
        @foreach ($subjects as $subject)
            <a href="{{ route('catalog', array_filter(['subject' => $subject->slug, 'q' => request('q')])) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request('subject') === $subject->slug ? 'bg-ink text-cream' : 'border border-ink/20' }}">{{ $subject->name }}</a>
        @endforeach
    </nav>

    @if ($courses->isEmpty())
        <div class="mt-16 rounded-[2rem] border-2 border-ink p-8">
            <h2 class="text-2xl font-black">دوره‌ای یافت نشد</h2>
            <p class="mt-3 leading-8 text-ink/65">این فضا با انتشار نخستین دوره‌های بررسی‌شده پر می‌شود.</p>
        </div>
    @else
        <div class="mt-16 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($courses as $course)
                <a href="{{ route('courses.show', $course) }}" class="interactive-card group rounded-[2rem] border border-ink/15 p-6 transition">
                    <p class="text-sm font-bold text-coral">{{ $course->subject->name }}</p>
                    <h2 class="mt-8 text-2xl font-black">{{ $course->title }}</h2>
                    <p class="mt-3 leading-7 text-ink/65">{{ $course->excerpt }}</p>
                    <p class="mt-8 text-sm font-bold">مشاهدهٔ دوره ←</p>
                </a>
            @endforeach
        </div>
        <div class="mt-10">{{ $courses->links() }}</div>
    @endif
</section>
@endsection
