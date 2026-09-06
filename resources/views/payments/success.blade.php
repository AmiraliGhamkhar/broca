@extends('layouts.app')

@section('title', 'پرداخت موفق — ' . __('app.name'))
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-white border border-hairline-soft rounded-3xl p-8 shadow-float">
            <svg class="mx-auto h-16 w-16 text-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <h1 class="mt-4 text-2xl font-bold">پرداخت با موفقیت انجام شد</h1>
            <p class="mt-2 text-muted">اشتراک شما فعال شده است. از استفاده از خدمات بروکا لذت ببرید.</p>

            <dl class="mt-8 grid gap-3 text-sm">
                <div class="flex items-center justify-between rounded-xl bg-surface-soft px-4 py-3">
                    <dt class="font-bold">شمارهٔ فاکتور</dt>
                    <dd class="font-bold flex items-center gap-2" dir="ltr">
                        {{ $invoice->number }}
                        {{-- Copy affordance: support asks users to read this
                             string back — one click removes transcription
                             errors, with a 1.5s confirmation tick (M-2). --}}
                        <button type="button"
                                x-data="{ copied: false }"
                                @click="navigator.clipboard.writeText(@js($invoice->number)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1500); })"
                                class="inline-flex items-center gap-1 rounded-full border border-hairline bg-white px-2.5 py-1 text-[11px] font-bold text-muted hover:text-ink hover:border-ink transition-colors"
                                :aria-label="copied ? 'کپی شد' : 'کپی شمارهٔ فاکتور'">
                            <span x-show="!copied" x-transition.opacity.duration.150ms>کپی</span>
                            <span x-show="copied" x-cloak x-transition.opacity.duration.150ms class="text-teal">کپی شد ✓</span>
                        </button>
                    </dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-surface-soft px-4 py-3">
                    <dt class="font-bold">پلن</dt>
                    <dd class="font-bold">{{ $invoice->plan?->name }}</dd>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-surface-soft px-4 py-3">
                    <dt class="font-bold">مبلغ</dt>
                    <dd class="font-bold">{{ number_format((int) $invoice->amount_irr / 10) }} تومان</dd>
                </div>
                @if ($subscription?->ends_at)
                    <div class="flex items-center justify-between rounded-xl bg-surface-soft px-4 py-3">
                        <dt class="font-bold">اعتبار تا</dt>
                        <dd class="font-bold">{{ $subscription->ends_at->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</dd>
                    </div>
                @endif
            </dl>

            <a href="{{ route('dashboard') }}" class="inline-block mt-8 px-7 py-3 rounded bg-rausch text-white font-bold hover:bg-rausch-active transition-colors">رفتن به داشبورد</a>
        </div>
    </section>
@endsection
