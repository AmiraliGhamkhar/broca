@extends('layouts.app')

@section('title', '۴۰۴ — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <p class="text-7xl font-bold text-broca-accent">۴۰۴</p>
        <h1 class="mt-4 text-2xl font-bold">صفحه مورد نظر یافت نشد</h1>
        <p class="mt-3 text-broca-slate">ممکن است آدرس تغییر کرده باشد یا صفحه حذف شده باشد.</p>
        <a href="{{ url('/') }}" class="inline-block mt-8 px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">
            بازگشت به صفحه اصلی
        </a>
    </section>
@endsection
