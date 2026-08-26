@extends('layouts.app')

@section('title', 'مدیریت ویدیوها — ' . __('app.name'))

@section('content')
<section class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold">ویدیوها</h1>
    <a href="{{ route('admin.videos.create') }}" class="inline-block mt-4 px-5 py-2 rounded-md bg-broca-accent text-white">افزودن ویدیو</a>

    <div class="mt-6 bg-white border border-broca-sand rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-broca-sand"><th class="p-3 text-right">عنوان</th><th class="p-3 text-right">وضعیت</th><th class="p-3 text-right">آزاد</th><th class="p-3 text-right">عملیات</th></tr></thead>
            <tbody>
                @foreach($videos as $video)
                <tr class="border-b border-broca-sand">
                    <td class="p-3">{{ $video->title }}</td>
                    <td class="p-3">{{ $video->status }}</td>
                    <td class="p-3">{{ $video->is_free_designated ? 'بله' : 'خیر' }}</td>
                    <td class="p-3 flex gap-2">
                        <a href="{{ route('admin.videos.edit', $video) }}">ویرایش</a>
                        <form method="post" action="{{ route('admin.videos.destroy', $video) }}" style="display:inline">@csrf @method('delete')<button type="submit" onclick="return confirm('حذف شود؟')">حذف</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $videos->links() }}</div>
</section>
@endsection
