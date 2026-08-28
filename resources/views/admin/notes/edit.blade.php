@extends('layouts.app')

@section('title', ($note->exists ? 'ویرایش جزوه: ' . $note->title : 'ایجاد جزوه جدید') . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="form-panel">
        <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
            <div>
                <h2 class="text-2xl font-black text-ink">{{ $note->exists ? 'ویرایش جزوه' : 'افزودن جزوه جدید' }}</h2>
                <p class="text-xs text-broca-slate mt-1">مشخصات فایل، دوره مربوطه، مسیر ذخیره خصوصی و وضعیت دسترسی</p>
            </div>
            <a href="{{ route('admin.notes.index') }}" class="text-xs font-bold text-coral underline">بازگشت به لیست جزوات</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ $note->exists ? route('admin.notes.update', $note) : route('admin.notes.store') }}" class="mt-6 space-y-5">
            @csrf
            @if ($note->exists)
                @method('patch')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-xs font-black text-ink">عنوان جزوه</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $note->title) }}" required
                           placeholder="مثال: خلاصه نموداری الکتروفیزیولوژی قلب"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="course_id" class="block text-xs font-black text-ink">دوره آموزشی</label>
                    <select id="course_id" name="course_id" required class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب دوره —</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ (string)old('course_id', $note->course_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-black text-ink">توضیحات و محتوای جزوه</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="توضیح کوتاه درباره محتوای این جزوه..."
                          class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-medium bg-white/70">{{ old('description', $note->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="storage_key" class="block text-xs font-black text-ink">کلید ذخیره‌سازی در دیسک خصوصی (Storage Key)</label>
                    <input type="text" id="storage_key" name="storage_key" value="{{ old('storage_key', $note->storage_key) }}"
                           placeholder="notes/sample-note.pdf" dir="ltr"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-mono bg-white/70">
                </div>

                <div>
                    <label for="mime_type" class="block text-xs font-black text-ink">نوع فایل (MIME Type)</label>
                    <input type="text" id="mime_type" name="mime_type" value="{{ old('mime_type', $note->mime_type ?? 'application/pdf') }}" dir="ltr"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-mono bg-white/70">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="author_id" class="block text-xs font-black text-ink">استاد / نویسنده علمی</label>
                    <select id="author_id" name="author_id" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="">— انتخاب نویسنده —</option>
                        @foreach ($contributors as $c)
                            <option value="{{ $c->id }}" {{ (string)old('author_id', $note->author_id) === (string)$c->id ? 'selected' : '' }}>
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
                            <option value="{{ $c->id }}" {{ (string)old('reviewer_id', $note->reviewer_id) === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->credentials }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="sort_order" class="block text-xs font-black text-ink">ترتیب نمایش</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $note->sort_order ?? 0) }}"
                           class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                </div>

                <div>
                    <label for="status" class="block text-xs font-black text-ink">وضعیت</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-3 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        @foreach (['draft' => 'پیش‌نویس', 'in_review' => 'در بازبینی', 'published' => 'منتشر شده', 'archived' => 'بایگانی'] as $st => $label)
                            <option value="{{ $st }}" {{ old('status', $note->status ?? 'draft') === $st ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_free_designated" value="1" {{ old('is_free_designated', $note->is_free_designated) ? 'checked' : '' }} class="rounded border-ink/20">
                        <span class="text-xs font-bold text-ink">سهمیه رایگان دوره (حداکثر ۱ جزوه)</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-broca-sand">
                <button type="submit" class="rounded-full bg-ink px-7 py-3 text-xs font-black text-cream hover:bg-coral transition-all">
                    {{ $note->exists ? 'ذخیره تغییرات جزوه' : 'ثبت جزوه جدید' }}
                </button>
                <a href="{{ route('admin.notes.index') }}" class="px-5 py-3 rounded-full border border-ink/20 text-xs font-bold text-broca-slate hover:bg-broca-sand">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</section>
@endsection
