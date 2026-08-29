@extends('layouts.app')

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-4xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <a href="{{ route('courses.show', $course) }}" class="text-sm font-bold text-rausch underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-ink">بازگشت به دوره</a>
    <p class="mt-12 text-sm font-bold text-rausch">جزوهٔ دوره</p>
    <h1 class="mt-4 font-display text-4xl sm:text-6xl leading-tight">{{ $note->title }}</h1>
    @if ($note->description)<p class="mt-6 max-w-2xl text-lg leading-9 text-muted">{{ $note->description }}</p>@endif
    <div class="mt-8 flex flex-wrap gap-3 text-sm font-bold">
        <span class="rounded-full bg-surface-soft px-4 py-2 font-bold">نویسنده: {{ $note->author?->name ?: '[PLACEHOLDER: نویسنده]' }} · {{ $note->author?->credentials ?: '[PLACEHOLDER: مدرک نویسنده]' }}</span>
        <span class="rounded-full bg-teal/10 text-teal px-4 py-2 font-bold">بازبینی: {{ $note->reviewer?->name ?: '[PLACEHOLDER: بازبین پزشکی]' }} · {{ $note->reviewer?->credentials ?: '[PLACEHOLDER: مدرک بازبین]' }}</span>
    </div>
    <div class="mt-14 rounded-3xl bg-ink p-8 text-white sm:p-12">
        <p class="text-lg font-bold">نسخهٔ خصوصی جزوه</p>
        <p class="mt-3 leading-8 text-white/65">فایل فقط پس از بررسی دسترسی از فضای خصوصی خوانده می‌شود و نشانی عمومی ندارد.</p>
        <a href="{{ route('notes.download', $note) }}" class="mt-8 inline-flex rounded-full bg-rausch px-6 py-3 font-bold text-white hover:bg-rausch-active transition-colors">دریافت جزوه</a>
    </div>
</section>
@endsection
