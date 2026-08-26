@extends('layouts.app')

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-4xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <a href="{{ route('courses.show', $course) }}" class="text-sm font-black text-coral underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">بازگشت به دوره</a>
    <p class="mt-12 text-sm font-black text-coral">جزوهٔ دوره</p>
    <h1 class="mt-4 text-5xl font-black leading-tight sm:text-7xl">{{ $note->title }}</h1>
    @if ($note->description)<p class="mt-6 max-w-2xl text-lg leading-9 text-ink/65">{{ $note->description }}</p>@endif
    <div class="mt-8 flex flex-wrap gap-3 text-sm font-bold">
        <span class="rounded-full bg-sun px-4 py-2">نویسنده: {{ $note->author?->name ?: '[PLACEHOLDER: نویسنده]' }} · {{ $note->author?->credentials ?: '[PLACEHOLDER: مدرک نویسنده]' }}</span>
        <span class="rounded-full bg-teal px-4 py-2">بازبینی: {{ $note->reviewer?->name ?: '[PLACEHOLDER: بازبین پزشکی]' }} · {{ $note->reviewer?->credentials ?: '[PLACEHOLDER: مدرک بازبین]' }}</span>
    </div>
    <div class="mt-14 rounded-[2rem] bg-ink p-8 text-cream sm:p-12">
        <p class="text-lg font-black">نسخهٔ خصوصی جزوه</p>
        <p class="mt-3 leading-8 text-cream/65">فایل فقط پس از بررسی دسترسی از فضای خصوصی خوانده می‌شود و نشانی عمومی ندارد.</p>
        <a href="{{ route('notes.download', $note) }}" class="mt-8 inline-flex rounded-full bg-sun px-6 py-3 font-black text-ink">دریافت جزوه</a>
    </div>
</section>
@endsection
