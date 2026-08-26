@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-12 lg:py-28"><p class="text-sm font-black text-coral">دسترسی به یادگیری</p><h1 class="mt-4 text-5xl font-black sm:text-7xl">اشتراک‌ها</h1><p class="mt-5 max-w-2xl leading-8 text-ink/65">[PLACEHOLDER: قیمت و جزئیات نهایی پلن‌ها پس از تصمیم تجاری ثبت می‌شود.]</p><div class="mt-16 grid gap-5 md:grid-cols-3">@forelse ($plans as $plan)<article class="rounded-[2rem] border-2 border-ink p-6"><p class="text-sm font-black text-coral">{{ $plan->name }}</p><h2 class="mt-8 text-3xl font-black">{{ $plan->duration_months ? $plan->duration_months.' ماهه' : 'رایگان' }}</h2><p class="mt-5 leading-7 text-ink/65">{{ $plan->description ?: '[PLACEHOLDER: توضیح پلن]' }}</p><p class="mt-10 text-2xl font-black">[PLACEHOLDER: قیمت]</p></article>@empty<div class="rounded-[2rem] border-2 border-ink p-8">پلن‌ها پس از تأیید نهایی نمایش داده می‌شوند.</div>@endforelse</div></section>
@endsection
