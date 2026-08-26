@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-xl px-5 py-24 text-center sm:px-8">
    <p class="text-sm font-black text-coral">یک قدم مانده</p>
    <h1 class="mt-4 text-4xl font-black">ایمیلت را تأیید کن</h1>
    <p class="mt-5 leading-8 text-ink/65">لینک تأیید به ایمیل شما فرستاده شد. برای دسترسی به مسیرهای یادگیری، صندوق ورودی را بررسی کنید.</p>
    <form method="post" action="{{ route('verification.send') }}" class="mt-8">@csrf<button class="rounded-full bg-ink px-6 py-3 font-black text-cream">ارسال دوبارهٔ لینک</button></form>
</section>
@endsection
