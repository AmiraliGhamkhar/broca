@extends('layouts.app')

@section('title', 'پرداخت ناموفق — ' . __('app.name'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-white border border-hairline-soft rounded-3xl p-8 shadow-float">
            <x-ui.icon name="cross" class="mx-auto size-16 text-rausch" />
            <h1 class="mt-4 text-2xl font-bold">پرداخت ناموفق بود</h1>
            <p class="mt-2 text-muted">در پردازش پرداخت شما مشکلی رخ داده است. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.</p>

            @if ($invoice?->exists)
                <p class="mt-6 text-sm text-muted">فاکتور <span class="font-bold">{{ $invoice->number }}</span> — مبلغ {{ number_format((int) $invoice->amount_irr / 10) }} تومان</p>
            @endif

            <a href="{{ route('plans') }}" class="inline-block mt-8 px-7 py-3 rounded bg-rausch text-white font-bold hover:bg-rausch-active transition-colors">بازگشت به پلن‌ها</a>
        </div>
    </section>
@endsection
