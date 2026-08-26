@extends('layouts.app')

@section('title', 'گزارش فعالیت مدیران — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
    <p class="text-sm font-black text-coral">مدیریت</p>
    <h1 class="mt-4 text-4xl font-black sm:text-5xl">گزارش فعالیت مدیران</h1>
    <p class="mt-5 leading-8 text-ink/65">آخرین ۳۰۰ اقدام تغییردهنده در پنل مدیریت — چه کسی، چه زمانی، از چه IP و با چه نتیجه‌ای. مقادیر حساس (گذرواژه، کدهای تأیید) هرگز ثبت نمی‌شوند.</p>

    <div class="mt-12 overflow-x-auto rounded-[2rem] border border-ink/15">
        <table class="w-full text-sm">
            <thead class="bg-ink/5 text-right">
                <tr>
                    <th class="p-3 font-black">زمان</th>
                    <th class="p-3 font-black">مدیر</th>
                    <th class="p-3 font-black">اقدام</th>
                    <th class="p-3 font-black">IP</th>
                    <th class="p-3 font-black">کد پاسخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t border-ink/10">
                        <td class="p-3 whitespace-nowrap" dir="ltr">{{ $log->created_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}</td>
                        <td class="p-3 font-bold">{{ $log->user?->name ?? '—' }}</td>
                        <td class="p-3 font-mono text-xs" dir="ltr">{{ $log->action }}</td>
                        <td class="p-3 font-mono text-xs" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                        <td class="p-3">
                            <span class="rounded-full px-2 py-1 text-xs font-black {{ $log->status_code >= 400 ? 'bg-coral text-cream' : 'bg-teal/20 text-teal' }}">{{ $log->status_code }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-ink/60">هنوز اقدامی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('admin.dashboard') }}" class="mt-10 inline-block text-sm font-black text-coral underline">بازگشت به پیشخوان مدیریت</a>
</section>
@endsection
