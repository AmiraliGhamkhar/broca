@extends('layouts.app')

@section('title', 'ویرایش پلن اشتراک: ' . $plan->name . ' — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.2fr)_minmax(280px,0.8fr)] xl:items-start">
        <div class="form-panel p-8 sm:p-10">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                <div>
                    <span class="sr-only">فرم مدیریت پلن</span>
                    <h1 class="text-2xl font-black text-ink mt-4">ویرایش تعرفه پلن: {{ $plan->name }}</h1>
                    <p class="text-sm leading-7 text-muted mt-3">قیمت، مدت اعتبار، توضیح عمومی و وضعیت فعال‌بودن پلن را هم‌راستا با خرید و ربات تلگرام به‌روز نگه دارید.</p>
                </div>
                <a href="{{ route('admin.plans.index') }}" class="button-secondary">بازگشت به فهرست</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rausch/30 bg-rausch-tint p-4 text-xs font-bold text-rausch" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('admin.plans.update', $plan) }}" class="mt-6 space-y-6">
                @csrf
                @method('patch')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-bold text-ink mb-1.5">عنوان پلن</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm font-bold">
                    </div>
                    <div>
                        <label for="price_irr" class="block text-xs font-bold text-ink mb-1.5">مبلغ به ریال (IRR)</label>
                        <input type="number" id="price_irr" name="price_irr" min="0" value="{{ old('price_irr', $plan->price_irr) }}" required dir="ltr" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-mono font-bold">
                        <p class="mt-2 text-[11px] text-muted">معادل تقریبی: {{ number_format((int) old('price_irr', $plan->price_irr) / 10) }} تومان</p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-ink mb-1.5">توضیحات پلن</label>
                    <textarea id="description" name="description" rows="4" class="w-full rounded-3xl border border-ink/20 bg-white px-4 py-3 text-sm leading-8">{{ old('description', $plan->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="duration_months" class="block text-xs font-bold text-ink mb-1.5">مدت اعتبار (ماه)</label>
                        <input type="number" id="duration_months" name="duration_months" min="0" max="36" value="{{ old('duration_months', $plan->duration_months) }}" required class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div>
                        <label for="sort_order" class="block text-xs font-bold text-ink mb-1.5">ترتیب نمایش</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" class="w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-xs font-bold">
                    </div>
                    <div class="flex items-center rounded-2xl border border-hairline-soft bg-surface-soft px-4 py-4">
                        <label class="flex items-start gap-3 text-xs font-bold text-ink cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="mt-0.5 rounded border-ink/20">
                            <span>پلن برای خرید کاربران فعال باشد.</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-6 border-t border-hairline-soft">
                    <button type="submit" class="button-primary">
                        <x-ui.icon name="badge-check" class="size-4" />
                        ذخیره تغییرات پلن
                    </button>
                    <a href="{{ route('admin.plans.index') }}" class="button-secondary">انصراف</a>
                </div>
            </form>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-28">
            <div class="meta-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">راهنمای قیمت‌گذاری</h2>
                <div class="trust-list mt-4">
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="wallet" class="size-5" /></span>
                        <div>
                            <strong>هم‌راستایی با درگاه</strong>
                            <span>مبلغ نهایی باید با تنظیمات پرداخت و پیام‌های فروش در تلگرام/سایت یکسان باشد.</span>
                        </div>
                    </div>
                    <div class="trust-list-item is-soft">
                        <span class="icon-frame-soft"><x-ui.icon name="clock" class="size-5" /></span>
                        <div>
                            <strong>مدت اعتبار شفاف</strong>
                            <span>برای کاربر باید کاملاً روشن باشد که هر پلن چه مدت دسترسی ایجاد می‌کند.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="editorial-card is-soft">
                <h2 class="text-sm font-extrabold text-ink">وضعیت فعلی</h2>
                <div class="grid gap-3 mt-4 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted">کد پلن</span>
                        <span dir="ltr" class="font-mono text-ink">{{ $plan->code }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted">فعال</span>
                        <span class="{{ $plan->is_active ? 'badge-success' : 'badge-soft' }}">{{ $plan->is_active ? 'بله' : 'خیر' }}</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection
