@extends('layouts.app')

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="text-sm font-bold text-rausch">نتیجهٔ آزمون</p>
    <h1 class="mt-4 font-display text-4xl sm:text-6xl">{{ $quiz->title }}</h1>
    <div class="mt-14 rounded-3xl bg-white border border-hairline-soft p-8 sm:p-12 shadow-float"><p class="font-display text-7xl text-rausch">{{ $attempt->score_percent }}٪</p><p class="mt-4 text-xl font-bold">{{ $attempt->passed ? 'آزمون را با موفقیت گذراندی.' : 'این بار قبول نشد؛ دوباره تمرین کن.' }}</p><p class="mt-3 leading-8">{{ $attempt->correct_count }} پاسخ درست از {{ $attempt->question_count }} سؤال</p><a href="{{ route('quizzes.show', $quiz) }}" class="mt-8 inline-flex rounded-full bg-rausch px-6 py-3 font-bold text-white hover:bg-rausch-active transition-colors">تلاش دوباره</a></div>
</section>
@endsection
