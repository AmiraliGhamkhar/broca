@extends('layouts.app')

@section('title', $quiz->title . ' — ' . __('app.name'))

@section('content')
<section class="mx-auto max-w-4xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28">
    <a href="{{ route('courses.show', $quiz->course) }}" class="text-sm font-black text-coral underline-offset-4 hover:underline">بازگشت به دوره</a>
    <p class="mt-12 text-sm font-black text-coral">آزمون مرور</p>
    <h1 class="mt-4 text-5xl font-black sm:text-7xl">{{ $quiz->title }}</h1>
    @if ($questions->isEmpty())
        <div class="mt-14 rounded-[2rem] border-2 border-ink p-8"><h2 class="text-2xl font-black">این آزمون هنوز آماده نیست</h2><p class="mt-3 leading-8 text-ink/65">پس از انتشار سؤال‌های بررسی‌شده، آزمون اینجا نمایش داده می‌شود.</p></div>
    @else
        <form method="post" action="{{ route('quizzes.attempts.store', $quiz) }}" class="mt-14 space-y-8">@csrf
            @foreach ($questions as $question)
                <fieldset class="rounded-[2rem] border border-ink/15 p-7"><legend class="max-w-2xl text-xl font-black leading-8">{{ $loop->iteration }}. {{ $question->prompt }}</legend><div class="mt-6 space-y-3">@foreach ($question->options as $option)<label class="flex cursor-pointer items-start gap-3 rounded-xl bg-ink/5 p-4 leading-7 hover:bg-sun"><input required type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="mt-2"> <span>{{ $option->label }}</span></label>@endforeach</div></fieldset>
            @endforeach
            <button class="rounded-full bg-ink px-7 py-4 font-black text-cream">ثبت پاسخ‌ها</button>
        </form>
    @endif
</section>
@endsection
