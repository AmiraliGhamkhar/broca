@extends('layouts.app')

@section('title', 'پرداخت موفق — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-white border border-broca-sand rounded-[2rem] p-8">
            <svg class="mx-auto h-16 w-16 text-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <h1 class="mt-4 text-2xl font-black">پرداخت با موفقیت انجام شد</h1>
            <p class="mt-2 text-broca-slate">اشتراک شما فعال شده است. از استفاده از خدمات بروکا لذت ببرید.</p>

            <dl class="mt-8 grid gap-3 text-sm">
                <div class="flex items-center justify-between rounded-xl bg-cream px-4 py-3">
                    <dt class="font-bold">شمارهٔ فاکتور</dt>
                    <dd class="font-black">{{ $invoice->number }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-cream px-4 py-3">
                    <dt class="font-bold">پلن</dt>
                    <dd class="font-black">{{ $invoice->plan?->name }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-cream px-4 py-3">
                    <dt class="font-bold">مبلغ</dt>
                    <dd class="font-black">{{ number_format((int) $invoice->amount_irr / 10) }} تومان</dd>
                </div>
                @if ($subscription?->ends_at)
                    <div class="flex items-center justify-between rounded-xl bg-cream px-4 py-3">
                        <dt class="font-bold">اعتبار تا</dt>
                        <dd class="font-black">{{ $subscription->ends_at->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</dd>
                    </div>
                @endif
            </dl>

            <a href="{{ route('dashboard') }}" class="inline-block mt-8 px-7 py-3 rounded-full bg-broca-accent text-white font-bold">رفتن به داشبورد</a>
        </div>
    </section>
@endsection
