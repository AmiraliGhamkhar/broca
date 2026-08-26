@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28"><p class="section-label">موضوع</p><h1 class="mt-4 text-5xl font-black">{{ $subject->name }}</h1><p class="mt-5 max-w-2xl leading-8 text-ink/65">{{ $subject->description }}</p><div class="mt-16 grid gap-5 md:grid-cols-2 lg:grid-cols-3">@forelse ($courses as $course)<a href="{{ route('courses.show', $course) }}" class="interactive-card rounded-[2rem] border border-ink/15 p-6 transition"><h2 class="text-2xl font-black">{{ $course->title }}</h2><p class="mt-3 leading-7 text-ink/65">{{ $course->excerpt }}</p></a>@empty<p class="rounded-2xl border border-ink/15 p-6 font-bold">برای این موضوع هنوز دوره‌ای منتشر نشده است.</p>@endforelse</div><div class="mt-10">{{ $courses->links() }}</div></section>
@endsection
