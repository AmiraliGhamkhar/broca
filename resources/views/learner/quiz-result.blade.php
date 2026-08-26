@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-3xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <p class="text-sm font-black text-coral">نتیجهٔ آزمون</p>
    <h1 class="mt-4 text-5xl font-black sm:text-7xl">{{ $quiz->title }}</h1>
    <div class="mt-14 rounded-[2rem] bg-sun p-8 sm:p-12"><p class="text-7xl font-black">{{ $attempt->score_percent }}٪</p><p class="mt-4 text-xl font-black">{{ $attempt->passed ? 'آزمون را با موفقیت گذراندی.' : 'این بار قبول نشد؛ دوباره تمرین کن.' }}</p><p class="mt-3 leading-8">{{ $attempt->correct_count }} پاسخ درست از {{ $attempt->question_count }} سؤال</p><a href="{{ route('quizzes.show', $quiz) }}" class="mt-8 inline-flex rounded-full bg-ink px-6 py-3 font-black text-cream">تلاش دوباره</a></div>
</section>
@endsection
