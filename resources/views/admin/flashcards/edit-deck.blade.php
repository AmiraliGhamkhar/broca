@extends('layouts.app')

@section('title', ($deck->exists ? 'ویرایش دسته کارت: ' . $deck->title : 'ساخت دسته کارت جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
            <div>
                <h2 class="text-2xl font-black text-ink">{{ $deck->exists ? 'ویرایش دسته کارت' : 'ساخت دسته کارت جدید' }}</h2>
                <p class="text-xs text-broca-slate mt-1">دسته‌بندی کارت‌های مرور فاصله‌دار مربوط به هر دوره آموزشی</p>
            </div>
            <a href="{{ route('admin.flashcards.index') }}" class="text-xs font-bold text-coral underline">بازگشت</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $deck->exists ? route('admin.flashcards.decks.update', $deck) : route('admin.flashcards.decks.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($deck->exists)
                @method('patch')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-xs font-black text-ink">عنوان دسته کارت</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $deck->title) }}" required
                           placeholder="مثال: کارت‌های مرور الکتروفیزیولوژی قلب"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="course_id" class="block text-xs font-black text-ink">دوره آموزشی</label>
                    <select id="course_id" name="course_id" required class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب دوره —</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ (string)old('course_id', $deck->course_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-black text-ink">شرح دسته کارت</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="توضیحات تکمیلی درباره مفاهیم موجود در این کارت‌ها..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white/70">{{ old('description', $deck->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="author_id" class="block text-xs font-black text-ink">استاد / طراح کارت‌ها</label>
                    <select id="author_id" name="author_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب نویسنده —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('author_id', $deck->author_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reviewer_id" class="block text-xs font-black text-ink">بازبین علمی</label>
                    <select id="reviewer_id" name="reviewer_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب بازبین پزشکی —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('reviewer_id', $deck->reviewer_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="sort_order" class="block text-xs font-black text-ink">ترتیب نمایش</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $deck->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="status" class="block text-xs font-black text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $deck->status ?? 'draft') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-broca-sand">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-black text-cream hover:bg-coral transition-all">
                    {{ $deck->exists ? 'ذخیره تغییرات دسته کارت' : 'ثبت دسته کارت جدید' }}
                </button>
                <a href="{{ route('admin.flashcards.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-broca-slate hover:bg-broca-sand">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
