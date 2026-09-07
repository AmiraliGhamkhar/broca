@extends('layouts.app')

@section('title', 'لوگو و تصویر اصلی — مدیریت بروکا')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="mx-auto max-w-4xl editorial-card">
        <div class="border-b border-hairline-soft pb-5">
            <h2 class="text-xl font-black text-ink">هویت بصری سایت</h2>
            <p class="mt-2 text-sm text-muted">لوگوی سربرگ و تصویر بخش اصلی صفحه نخست را بارگذاری، جایگزین یا حذف کنید.</p>
        </div>

        <form method="post" action="{{ route('admin.appearance.update') }}" enctype="multipart/form-data" class="mt-6 space-y-8">
            @csrf
            @method('patch')

            <div class="grid gap-5 md:grid-cols-2">
                <div class="rounded-2xl border border-hairline-soft p-5">
                    <label for="logo" class="block text-sm font-extrabold">لوگوی سایت</label>
                    <p class="mt-1 text-xs text-muted">PNG، JPG یا WebP؛ حداکثر ۵ مگابایت</p>
                    @if ($settings->logo_image_path)
                        <img src="{{ $settings->logo_image_path }}" alt="لوگوی فعلی" class="mt-4 h-20 max-w-full rounded-xl object-contain">
                        <label class="mt-4 flex items-center gap-2 text-xs font-bold text-rausch"><input type="checkbox" name="remove_logo" value="1"> حذف لوگو و بازگشت به نشان پیش‌فرض</label>
                    @endif
                    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm">
                </div>

                <div class="rounded-2xl border border-hairline-soft p-5">
                    <label for="hero" class="block text-sm font-extrabold">تصویر اصلی صفحه نخست</label>
                    <p class="mt-1 text-xs text-muted">ترجیحاً عمودی و باکیفیت؛ حداکثر ۱۰ مگابایت</p>
                    <img src="{{ $settings->heroUrl() }}" alt="{{ $settings->hero_image_alt }}" class="mt-4 h-40 w-full rounded-xl object-cover">
                    @if ($settings->hero_image_path)
                        <label class="mt-4 flex items-center gap-2 text-xs font-bold text-rausch"><input type="checkbox" name="remove_hero" value="1"> حذف تصویر سفارشی و بازگشت به تصویر پیش‌فرض</label>
                    @endif
                    <input id="hero" name="hero" type="file" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm">
                </div>
            </div>

            <div>
                <label for="hero_image_alt" class="block text-sm font-extrabold">متن جایگزین تصویر اصلی</label>
                <p class="mt-1 text-xs text-muted">توصیف کوتاه و واقعی تصویر برای دسترس‌پذیری و موتورهای جستجو.</p>
                <input id="hero_image_alt" name="hero_image_alt" value="{{ old('hero_image_alt', $settings->hero_image_alt) }}" required maxlength="255" class="mt-2 w-full rounded-2xl border border-ink/20 bg-white px-4 py-3 text-sm">
                @error('hero_image_alt')<p class="mt-2 text-xs text-rausch">{{ $message }}</p>@enderror
            </div>

            <button class="button-primary" type="submit">ذخیره تغییرات ظاهر سایت</button>
        </form>
    </div>
</section>
@endsection
