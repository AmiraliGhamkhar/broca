@extends('layouts.app')

@section('title', 'مدیریت ویدیوها — ' . __('app.name'))

@section('content')
<section class="max-w-6xl mx-auto px-4 py-12 sm:py-16">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-3xl font-black">ویدیوها</h1>
        <a href="{{ route('admin.videos.create') }}" class="rounded-full bg-broca-accent px-5 py-2.5 font-bold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-ink">افزودن ویدیو</a>
    </div>

    @if ($errors->any())<div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-sm font-bold" role="alert">{{ $errors->first() }}</div>@endif

    <div class="surface-panel mt-8 overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-broca-sand">
                <th class="p-3 text-right">عنوان</th>
                <th class="p-3 text-right">دوره</th>
                <th class="p-3 text-right">وضعیت</th>
                <th class="p-3 text-right">آزاد</th>
                <th class="p-3 text-right">عملیات</th>
            </tr></thead>
            <tbody>
                @forelse ($videos as $video)
                <tr class="border-b border-broca-sand">
                    <td class="p-3 font-bold">{{ $video->title }}</td>
                    <td class="p-3">{{ $video->course?->title }}</td>
                    <td class="p-3">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $video->status === 'published' ? 'bg-teal/10 text-teal' : 'bg-broca-sand text-broca-slate' }}">{{ $video->status }}</span>
                    </td>
                    <td class="p-3">{{ $video->is_free_designated ? 'بله' : 'خیر' }}</td>
                    <td class="p-3">
                        <div class="flex gap-3">
                            <a class="underline hover:text-coral" href="{{ route('admin.videos.edit', $video) }}">ویرایش</a>
                            <form method="post" action="{{ route('admin.videos.destroy', $video) }}" style="display:inline">
                                @csrf @method('delete')
                                <button type="submit" class="underline hover:text-coral" onclick="return confirm('حذف شود؟')">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td class="p-6 text-center text-broca-slate" colspan="5">هنوز ویدیویی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $videos->links() }}</div>
</section>
@endsection
