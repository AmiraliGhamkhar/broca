@extends('layouts.app')

@section('title', 'بلاگ — ' . __('app.name'))

@section('content')
    <section class="max-w-4xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold">بلاگ</h1>
        <p class="mt-2 text-broca-slate">مطالب پزشکی و آموزشی برای دانشجویان</p>

        <div class="mt-8 grid gap-6">
            @for ($i = 1; $i <= 3; $i++)
                <article class="border-b border-broca-sand pb-6">
                    <h2 class="text-xl font-medium">{{ __('app.name') }} - مقاله نمونه {{ $i }}</h2>
                    <p class="mt-2 text-broca-slate">خلاصه مقاله نمونه {{ $i }}. این محتوای نمونه است و بعداً با محتوای واقعی جایگزین می‌شود.</p>
                    <a href="{{ route('blog.show', ['slug' => 'sample-article-' . $i]) }}" class="inline-block mt-3 px-4 py-1.5 rounded-md bg-broca-accent text-white">خواندن مقاله</a>
                </article>
            @endfor
        </div>
    </section>
@endsection
