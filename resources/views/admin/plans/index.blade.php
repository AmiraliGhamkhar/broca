@extends('layouts.app')

@section('title', 'مدیریت پلن‌های اشتراک — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,0.8fr)] xl:items-end">
        <div class="section-intro">
            <span class="eyebrow">تعرفه و دسترسی مالی</span>
            <h1 class="section-title mt-4">پلن‌های اشتراک و قیمت‌گذاری</h1>
            <p class="section-copy mt-5">قیمت ریالی، مدت اعتبار، توضیح مزایا و وضعیت فعال بودن پلن‌ها در این بخش کنترل می‌شود تا فرایند خرید و تمدید برای کاربران شفاف بماند.</p>
        </div>

        <div class="editorial-card is-soft flex items-start gap-3">
            <span class="icon-frame"><x-ui.icon name="wallet" class="size-5" /></span>
            <div>
                <p class="text-sm font-extrabold text-ink">اصل مهم قیمت‌گذاری</p>
                <p class="mt-2 text-xs leading-7 text-muted">نام پلن، قیمت و مدت باید به‌صورت هم‌راستا در سایت، پرداخت و تلگرام نمایش داده شوند تا اختلافی در تجربه کاربر ایجاد نشود.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 mt-10">
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">تعداد پلن‌ها</p>
            <p class="mt-3 text-3xl font-black text-ink">{{ number_format($plans->count()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">پلن‌های فعال</p>
            <p class="mt-3 text-3xl font-black text-teal">{{ number_format($plans->where('is_active', true)->count()) }}</p>
        </div>
        <div class="meta-card is-soft">
            <p class="text-xs font-bold text-ink">واحد ذخیره‌سازی</p>
            <p class="mt-3 text-sm font-bold leading-7 text-ink">همه مبالغ به ریال ذخیره می‌شوند و معادل تومانی برای مدیریت سریع نمایش داده می‌شود.</p>
        </div>
    </div>

    <div class="surface-panel mt-8 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="border-b border-hairline-soft bg-surface-soft text-right text-muted">
                <tr>
                    <th class="p-4 font-bold">پلن</th>
                    <th class="p-4 font-bold">کد</th>
                    <th class="p-4 font-bold text-center">مدت اعتبار</th>
                    <th class="p-4 font-bold text-left">قیمت تومان</th>
                    <th class="p-4 font-bold text-left">قیمت ریال</th>
                    <th class="p-4 font-bold">وضعیت</th>
                    <th class="p-4 font-bold text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @foreach ($plans as $plan)
                    <tr class="align-top hover:bg-white/50">
                        <td class="p-4">
                            <p class="text-sm font-black text-ink">{{ $plan->name }}</p>
                            @if ($plan->description)
                                <p class="mt-1 text-[11px] leading-6 text-muted">{{ \Illuminate\Support\Str::limit($plan->description, 100) }}</p>
                            @endif
                        </td>
                        <td class="p-4 font-mono text-[11px] text-muted" dir="ltr">{{ $plan->code }}</td>
                        <td class="p-4 text-center font-bold text-ink">{{ $plan->duration_months ? $plan->duration_months . ' ماه' : 'همیشگی' }}</td>
                        <td class="p-4 text-left font-black text-teal" dir="ltr">{{ number_format((int) $plan->price_irr / 10) }} تومان</td>
                        <td class="p-4 text-left font-mono text-muted" dir="ltr">{{ number_format((int) $plan->price_irr) }} IRR</td>
                        <td class="p-4">
                            <span class="{{ $plan->is_active ? 'badge-success' : 'badge-soft' }}">{{ $plan->is_active ? 'فعال برای خرید' : 'غیرفعال' }}</span>
                        </td>
                        <td class="p-4 text-left">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="button-soft">ویرایش تعرفه</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
