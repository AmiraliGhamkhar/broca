@extends('layouts.app')

@section('title', $subject->name . ' — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="space-y-3 pb-8 border-b border-broca-sand">
        <a href="{{ route('catalog') }}" class="text-xs font-black text-coral hover:underline">
            ← بازگشت به تمامی درس‌نامه‌ها
        </a>
        <h1 class="text-3xl sm:text-5xl font-black text-ink">{{ $subject->name }}</h1>
        <p class="text-xs sm:text-sm text-broca-slate max-w-2xl leading-7">
            {{ $subject->description }}
        </p>
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($courses as $course)
            <div class="interactive-card surface-panel p-6 rounded-3xl flex flex-col justify-between hover:shadow-lg transition-all">
                <div class="space-y-3">
                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black bg-sun text-ink">
                        {{ $course->level ?: 'علوم پایه' }}
                    </span>
                    <h2 class="text-lg font-black text-ink leading-7">
                        <a href="{{ route('courses.show', $course) }}" class="hover:text-coral transition-colors">
                            {{ $course->title }}
                        </a>
                    </h2>
                    <p class="text-xs text-broca-slate leading-6 line-clamp-3">
                        {{ $course->excerpt ?: $course->description }}
                    </p>
                </div>

                <div class="mt-8 pt-4 border-t border-broca-sand space-y-3">
                    <div class="flex items-center justify-between text-xs text-broca-slate">
                        <span class="font-bold text-ink">استاد: {{ $course->author->name ?? 'هیئت علمی' }}</span>
                        <span>بازبین: {{ $course->reviewer->name ?? 'متخصص' }}</span>
                    </div>

                    <a href="{{ route('courses.show', $course) }}"
                       class="block w-full py-2.5 rounded-full bg-ink/5 hover:bg-ink hover:text-cream text-center text-xs font-black text-ink transition-all">
                        مشاهده سرفصل‌ها و دروس ←
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 surface-panel p-12 text-center text-broca-slate space-y-2">
                <p class="text-sm font-bold text-ink">دوره‌ای در این درس‌نامه منتشر نشده است.</p>
                <a href="{{ route('catalog') }}" class="text-xs text-coral font-black underline">مشاهده سایر شاخه‌ها</a>
            </div>
        @endforelse
    </div>

    <div class="mt-10">{{ $courses->links() }}</div>
</section>
@endsection
