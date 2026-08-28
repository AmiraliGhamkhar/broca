@extends('layouts.app')

@section('title', 'مدیریت پلن‌های اشتراک — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">پلن‌های اشتراک و قیمت‌گذاری</h2>
            <p class="text-xs text-broca-slate mt-1">تنظیم قیمت ریالی، مدت زمان دسترسی و فعال/غیرفعال‌سازی پلن‌ها در درگاه پرداخت</p>
        </div>
    </div>

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">عنوان پلن</th>
                    <th class="p-3.5 font-black">کد شناسه</th>
                    <th class="p-3.5 font-black text-center">مدت اعتبار</th>
                    <th class="p-3.5 font-black text-left">قیمت (تومان)</th>
                    <th class="p-3.5 font-black text-left">قیمت (ریال درگاه)</th>
                    <th class="p-3.5 font-black">وضعیت</th>
                    <th class="p-3.5 font-black text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @foreach ($plans as $plan)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-black text-ink text-sm">{{ $plan->name }}</td>
                        <td class="p-3.5 font-mono text-[11px] text-broca-slate" dir="ltr">{{ $plan->code }}</td>
                        <td class="p-3.5 text-center font-bold text-ink">{{ $plan->duration_months ? $plan->duration_months . ' ماه' : 'همیشگی' }}</td>
                        <td class="p-3.5 text-left font-black text-sm text-teal" dir="ltr">{{ number_format((int) $plan->price_irr / 10) }} تومان</td>
                        <td class="p-3.5 text-left font-mono text-broca-slate" dir="ltr">{{ number_format((int) $plan->price_irr) }} IRR</td>
                        <td class="p-3.5">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $plan->is_active ? 'bg-teal/10 text-teal' : 'bg-coral/10 text-coral' }}">
                                {{ $plan->is_active ? 'فعال در پرداخت ✓' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td class="p-3.5 text-left">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">
                                ویرایش تعرفه
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
