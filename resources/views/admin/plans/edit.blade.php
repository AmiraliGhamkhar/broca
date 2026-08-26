@extends('layouts.app')

@section('title', 'ویرایش پلان — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-xl px-5 py-20 sm:px-8">
    <p class="text-sm font-black text-coral">مدیریت</p>
    <h1 class="mt-4 text-4xl font-black sm:text-5xl">ویرایش «{{ $plan->name }}»</h1>

    <form method="post" action="{{ route('admin.plans.update', $plan) }}" class="mt-10 space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-bold" for="name">نام پلان</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $plan->name) }}" class="w-full rounded-xl border border-ink/20 bg-transparent p-2 mt-1">
            @error('name')<p class="mt-1 text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-bold" for="description">توضیح</label>
            <textarea id="description" name="description" rows="3" class="w-full rounded-xl border border-ink/20 bg-transparent p-2 mt-1">{{ old('description', $plan->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-bold" for="price_irr">قیمت (ریال — عدد صحیح)</label>
            <input id="price_irr" name="price_irr" type="number" min="0" step="1" required value="{{ old('price_irr', $plan->price_irr) }}" class="w-full rounded-xl border border-ink/20 bg-transparent p-2 mt-1" dir="ltr">
            <p class="mt-1 text-xs text-ink/60">معادل {{ number_format((int) old('price_irr', $plan->price_irr) / 10) }} تومان. برای پلان رایگان صفر بگذارید.</p>
            @error('price_irr')<p class="mt-1 text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-bold" for="duration_months">مدت (ماه)</label>
            <input id="duration_months" name="duration_months" type="number" min="0" step="1" required value="{{ old('duration_months', $plan->duration_months) }}" class="w-full rounded-xl border border-ink/20 bg-transparent p-2 mt-1" dir="ltr">
            @error('duration_months')<p class="mt-1 text-sm font-bold text-coral" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-bold" for="sort_order">ترتیب نمایش</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $plan->sort_order) }}" class="w-full rounded-xl border border-ink/20 bg-transparent p-2 mt-1" dir="ltr">
        </div>

        <label class="flex items-center gap-3 font-bold">
            <input type="checkbox" name="is_active" value="1" class="size-4" @checked(old('is_active', $plan->is_active))>
            پلان برای خرید فعال باشد
        </label>

        <button type="submit" class="rounded-full bg-ink px-7 py-4 font-black text-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">ذخیرهٔ تغییرات</button>
    </form>

    <a href="{{ route('admin.plans.index') }}" class="mt-10 inline-block text-sm font-black text-coral underline">بازگشت به فهرست پلان‌ها</a>
</section>
@endsection
