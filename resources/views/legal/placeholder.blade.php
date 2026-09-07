@extends('layouts.app')

@php
    // Body copy lives in App\Support\LegalContent so the HTML page and the
    // Markdown twin (/{page}.md) can never drift apart.
    $document = \App\Support\LegalContent::document($page);
    $heading = $document['heading'];
@endphp

@section('title', $heading . ' — ' . __('app.name'))

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="space-y-3 pb-8 border-b border-hairline-soft">
        <span class="text-xs font-bold text-rausch-text">اسناد حقوقی و تعهدات بالینی</span>
        <h1 class="text-3xl sm:text-4xl font-bold text-ink">{{ $heading }}</h1>
        <p class="text-xs text-muted">آخرین به‌روزرسانی: ۱۴۰۳/۰۶/۰۱ · نسخه: {{ config('broca.terms_version') }}</p>
    </div>

    <div class="mt-8 surface-panel p-8 sm:p-10 text-xs sm:text-sm text-ink/85 leading-8 space-y-6">
        @foreach ($document['sections'] as $index => $section)
            @if ($page === 'medical-disclaimer' && $index === 0)
                <div class="p-4 rounded-2xl bg-rausch/10 border border-coral/30 text-rausch-text font-bold space-y-2">
                    <span class="text-base block">⚖️ {{ $section['h'] }}</span>
                    <p>{{ $section['p'] }}</p>
                </div>
            @else
                <h2 class="text-base font-bold text-ink">{{ $section['h'] }}</h2>
                <p>{{ $section['p'] }}</p>
            @endif
        @endforeach
    </div>
</section>
@endsection
