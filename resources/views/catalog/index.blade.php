@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <div class="flex flex-col justify-between gap-8 sm:flex-row sm:items-end"><div><p class="text-sm font-black text-coral">کتابخانهٔ بروکا</p><h1 class="mt-4 text-5xl font-black sm:text-7xl">دوره‌ها</h1></div><form method="get" class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="جست‌وجوی دوره" class="rounded-full border border-ink/20 bg-transparent px-5 py-3" /><button class="rounded-full bg-ink px-5 py-3 font-black text-cream">جست‌وجو</button></form></div>
    @if ($courses->isEmpty())<div class="mt-16 rounded-[2rem] border-2 border-ink p-8"><h2 class="text-2xl font-black">هنوز دوره‌ای منتشر نشده</h2><p class="mt-3 leading-8 text-ink/65">این فضا با انتشار نخستین دوره‌های بررسی‌شده پر می‌شود.</p></div>@else
    <div class="mt-16 grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach ($courses as $course)<a href="{{ route('courses.show', $course) }}" class="group rounded-[2rem] border border-ink/15 p-6 transition hover:-translate-y-1 hover:border-coral"><p class="text-sm font-bold text-coral">{{ $course->subject->name }}</p><h2 class="mt-8 text-2xl font-black">{{ $course->title }}</h2><p class="mt-3 leading-7 text-ink/65">{{ $course->excerpt }}</p><p class="mt-8 text-sm font-bold">مشاهدهٔ دوره ←</p></a>@endforeach</div><div class="mt-10">{{ $courses->links() }}</div>@endif
</section>
@endsection
