@extends('layouts.app')

@section('title', 'پرداخت موفق — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-green-50 border border-green-200 rounded-lg p-8">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <h1 class="mt-4 text-2xl font-bold">پرداخت با موفقیت انجام شد</h1>
            <p class="mt-2 text-broca-slate">اشتراک شما فعال شده است. از استفاده از خدمات بروکا لذت ببرید.</p>
            <a href="{{ route('dashboard') }}" class="inline-block mt-6 px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">رفتن به داشبورد</a>
        </div>
    </section>
@endsection
