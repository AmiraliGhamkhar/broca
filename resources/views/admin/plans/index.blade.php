@extends('layouts.app')

@section('title', 'مدیریت پلان‌ها — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-5xl px-5 py-20 sm:px-8">
    <p class="text-sm font-black text-coral">مدیریت</p>
    <h1 class="mt-4 text-4xl font-black sm:text-5xl">پلان‌های اشتراک</h1>
    <p class="mt-5 leading-8 text-ink/65">قیمت‌ها در ریال و به‌صورت عدد صحیح ذخیره می‌شوند و همه‌جا از همین رکوردها خوانده می‌شوند — هیچ قیمتی در کد ثابت نیست. افزودن یا حذف پلان تغییر محصولی است و باید توسط مهندس انجام شود.</p>

    <div class="mt-12 overflow-x-auto rounded-[2rem] border border-ink/15">
        <table class="w-full text-sm">
            <thead class="bg-ink/5 text-right">
                <tr>
                    <th class="p-4 font-black">پلان</th>
                    <th class="p-4 font-black">مدت</th>
                    <th class="p-4 font-black">قیمت (تومان)</th>
                    <th class="p-4 font-black">وضعیت</th>
                    <th class="p-4 font-black"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    <tr class="border-t border-ink/10">
                        <td class="p-4 font-black">{{ $plan->name }}</td>
                        <td class="p-4">{{ $plan->duration_months }} ماه</td>
                        <td class="p-4 font-bold" dir="ltr">{{ number_format((int) $plan->price_irr / 10) }}</td>
                        <td class="p-4">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $plan->is_active ? 'bg-teal text-cream' : 'bg-ink/10 text-ink/60' }}">{{ $plan->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="p-4">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="font-black text-coral underline">ویرایش</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="mt-10 inline-block text-sm font-black text-coral underline">بازگشت به پیشخوان مدیریت</a>
</section>
@endsection
