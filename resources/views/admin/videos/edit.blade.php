@extends('layouts.app')

@section('title', ($video->exists ? 'ویرایش ویدیو' : 'ایجاد ویدیو') . ' — ' . __('app.name'))

@section('content')
<section class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold">{{ $video->exists ? 'ویرایش ویدیو' : 'ایجاد ویدیو' }}</h1>
    <form method="post" action="{{ $video->exists ? route('admin.videos.update', $video) : route('admin.videos.store') }}" class="mt-6 space-y-4">
        @csrf @if($video->exists) @method('patch') @endif
        <div><label class="block text-sm font-medium">عنوان</label><input name="title" value="{{ old('title', $video->title) }}" required class="w-full p-2 border rounded"></div>
        <div><label class="block text-sm font-medium">شرح</label><textarea name="description" rows="3" class="w-full p-2 border rounded">{{ old('description', $video->description) }}</textarea></div>
        <div><label class="block text-sm font-medium">زمان (ثانیه)</label><input name="duration_seconds" type="number" min="0" value="{{ old('duration_seconds', $video->duration_seconds) }}" class="w-full p-2 border rounded"></div>
        <div><label class="flex items-center gap-2"><input name="is_free_designated" type="checkbox" value="1" {{ old('is_free_designated', $video->is_free_designated) ? 'checked' : '' }} class="mt-1"> آزاد معرفی شود</label></div>
        <div><label class="block text-sm font-medium">وضعیت</label>
            <select name="status" class="w-full p-2 border rounded">{{ collect(['draft','in_review','published','archived'])->map(fn($s)=>'<option value="'.$s.'"'.($s===old('status',$video->status)?' selected':'').'>'.$s.'</option>')->implode('') }}</select>
        </div>
        <button type="submit" class="px-5 py-2 rounded-md bg-broca-accent text-white">ذخیره</button>
        <a href="{{ route('admin.videos.index') }}" class="inline-block px-4 py-2 text-broca-slate">انصراف</a>
    </form>
</section>
@endsection
