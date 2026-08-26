@extends('layouts.app')

@section('title', 'پرداخت ناموفق — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-red-50 border border-red-200 rounded-lg p-8">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <h1 class="mt-4 text-2xl font-bold">پرداخت ناموفق بود</h1>
            <p class="mt-2 text-broca-slate">در پردازش پرداخت شما مشکلی رخ داده است. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.</p>
            <a href="{{ route('plans') }}" class="inline-block mt-6 px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">بازگشت به پلن‌ها</a>
        </div>
    </section>
@endsection
