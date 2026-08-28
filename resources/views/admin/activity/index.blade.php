@extends('layouts.app')

@section('title', 'گزارش لاگ فعالیت مدیران — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">گزارش و ردپای تغییرات مدیران (Audit Trail)</h2>
            <p class="text-xs text-broca-slate mt-1">ثبت ۳۰۰ عملیات تغییردهنده اخیر شامل مشخصات مدیر، IP، زمان دقیق و کدهای پاسخ</p>
        </div>
    </div>

    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">زمان ثبت</th>
                    <th class="p-3.5 font-black">مدیر مسئول</th>
                    <th class="p-3.5 font-black">عملیات / متد</th>
                    <th class="p-3.5 font-black">مسیر و پارامترها</th>
                    <th class="p-3.5 font-black text-center">آدرس IP</th>
                    <th class="p-3.5 font-black text-center">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($logs as $log)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 text-broca-slate whitespace-nowrap" dir="ltr">
                            {{ $log->created_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i:s') }}
                        </td>
                        <td class="p-3.5 font-bold text-ink">
                            {{ $log->actor_name_snapshot ?: $log->user?->name ?: 'سیستم' }}
                            <span class="block text-[10px] text-broca-slate">{{ $log->actor_email_snapshot ?: $log->user?->email }}</span>
                        </td>
                        <td class="p-3.5 font-mono text-[11px] font-bold text-ink" dir="ltr">
                            <span class="px-2 py-0.5 rounded bg-ink/5 font-mono">{{ $log->method }}</span>
                            <span class="text-coral">{{ $log->route_name ?: $log->url }}</span>
                        </td>
                        <td class="p-3.5 max-w-xs truncate text-[11px] text-broca-slate font-mono" dir="ltr">
                            {{ json_encode($log->payload, JSON_UNESCAPED_UNICODE) }}
                        </td>
                        <td class="p-3.5 text-center font-mono text-[11px] text-broca-slate" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                        <td class="p-3.5 text-center">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $log->status_code < 400 ? 'bg-teal/10 text-teal' : 'bg-coral/10 text-coral' }}">
                                {{ $log->status_code }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-sm text-broca-slate">هنوز لاگی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
