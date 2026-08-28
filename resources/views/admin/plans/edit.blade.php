@extends('layouts.app')

@section('title', 'ویرایش پلن اشتراک: ' . $plan->name . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
            <div>
                <h2 class="text-2xl font-black text-ink">ویرایش تعرفه پلن: {{ $plan->name }}</h2>
                <p class="text-xs text-broca-slate mt-1">مبالغ به ریال ذخیره و در درگاه به صورت قطعی اعتبارسنجی می‌شوند.</p>
            </div>
            <a href="{{ route('admin.plans.index') }}" class="text-xs font-bold text-coral underline">بازگشت</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('admin.plans.update', $plan) }}" class="mt-6 space-y-5">
            @csrf @method('patch')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-black text-ink">عنوان پلن</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" required
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="price_irr" class="block text-xs font-black text-ink">مبلغ به ریال (IRR)</label>
                    <input type="number" id="price_irr" name="price_irr" min="0" value="{{ old('price_irr', $plan->price_irr) }}" required dir="ltr"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-mono font-bold bg-white/70">
                    <p class="text-[11px] text-broca-slate mt-1">معادل: {{ number_format((int) old('price_irr', $plan->price_irr) / 10) }} تومان</p>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-black text-ink">توضیحات پلن</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white/70">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="duration_months" class="block text-xs font-black text-ink">مدت اعتبار (ماه)</label>
                    <input type="number" id="duration_months" name="duration_months" min="0" max="36" value="{{ old('duration_months', $plan->duration_months) }}" required
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="sort_order" class="block text-xs font-black text-ink">ترتیب نمایش</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="rounded border-ink/20">
                        <span class="text-xs font-bold text-ink">پلن فعال برای خرید کاربران</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-broca-sand">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-black text-cream hover:bg-coral transition-all">
                    ذخیره تغییرات پلن
                </button>
                <a href="{{ route('admin.plans.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-broca-slate hover:bg-broca-sand">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
