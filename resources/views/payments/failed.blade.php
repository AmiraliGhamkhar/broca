@extends('layouts.app')

@section('title', 'پرداخت ناموفق — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-white border border-broca-sand rounded-[2rem] p-8">
            <svg class="mx-auto h-16 w-16 text-coral" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <h1 class="mt-4 text-2xl font-black">پرداخت ناموفق بود</h1>
            <p class="mt-2 text-broca-slate">در پردازش پرداخت شما مشکلی رخ داده است. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.</p>

            @if ($invoice?->exists)
                <p class="mt-6 text-sm text-broca-slate">فاکتور <span class="font-black">{{ $invoice->number }}</span> — مبلغ {{ number_format((int) $invoice->amount_irr / 10) }} تومان</p>
            @endif

            <a href="{{ route('plans') }}" class="inline-block mt-8 px-7 py-3 rounded-full bg-broca-accent text-white font-bold">بازگشت به پلن‌ها</a>
        </div>
    </section>
@endsection
