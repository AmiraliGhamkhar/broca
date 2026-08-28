@extends('layouts.app')

@section('title', ($subject->exists ? 'ویرایش مبحث: ' . $subject->name : 'ایجاد مبحث جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
            <div>
                <h2 class="text-2xl font-black text-ink">{{ $subject->exists ? 'ویرایش مبحث / شاخه علمی' : 'ایجاد مبحث جدید' }}</h2>
                <p class="text-xs text-broca-slate mt-1">عنوان، توضیحات و ترتیب نمایش در صفحه کاتالوگ</p>
            </div>
            <a href="{{ route('admin.subjects.index') }}" class="text-xs font-bold text-coral underline">بازگشت</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $subject->exists ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($subject->exists)
                @method('patch')
            @endif

            <div>
                <label for="name" class="block text-xs font-black text-ink">نام مبحث (فارسی)</label>
                <input type="text" id="name" name="name" value="{{ old('name', $subject->name) }}" required
                       placeholder="مثال: فیزیولوژی پزشکی یا آناتومی بالینی"
                       class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
            </div>

            <div>
                <label for="description" class="block text-xs font-black text-ink">توضیحات کوتاه</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="توضیح کوتاه در مورد این شاخه از علوم پزشکی..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white/70">{{ old('description', $subject->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="sort_order" class="block text-xs font-black text-ink">ترتیب نمایش در کاتالوگ</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $subject->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', $subject->is_visible) ? 'checked' : '' }} class="rounded border-ink/20">
                        <span class="text-xs font-bold text-ink">قابل مشاهده در کاتالوگ و صفحه اول</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-broca-sand">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-black text-cream hover:bg-coral transition-all">
                    {{ $subject->exists ? 'ذخیره تغییرات مبحث' : 'ثبت مبحث جدید' }}
                </button>
                <a href="{{ route('admin.subjects.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-broca-slate hover:bg-broca-sand">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
