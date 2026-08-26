@extends('layouts.app')

@section('title', 'داشبورد — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28"><p class="text-sm font-black text-coral">فضای یادگیری</p><h1 class="mt-4 text-5xl font-black sm:text-7xl">سلام {{ auth()->user()->name }}</h1><p class="mt-5 leading-8 text-ink/65">دوره‌هایی که در آن‌ها ثبت‌نام کرده‌ای اینجا جمع می‌شوند.</p><div class="mt-16 grid gap-5 md:grid-cols-2 lg:grid-cols-3">@forelse ($enrollments as $enrollment)<a href="{{ route('courses.show', $enrollment->course) }}" class="rounded-[2rem] bg-sun p-6"><p class="text-sm font-bold">{{ $enrollment->course->subject->name }}</p><h2 class="mt-8 text-2xl font-black">{{ $enrollment->course->title }}</h2><p class="mt-8 text-sm font-bold">ادامهٔ مسیر ←</p></a>@empty<div class="rounded-[2rem] border-2 border-ink p-8"><h2 class="text-2xl font-black">هنوز دوره‌ای نداری</h2><a href="{{ route('catalog') }}" class="mt-5 inline-block font-black text-coral underline">رفتن به کاتالوگ</a></div>@endforelse</div></section>
@endsection
