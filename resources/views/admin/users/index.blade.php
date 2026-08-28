@extends('layouts.app')

@section('title', 'مدیریت کاربران و دسترسی‌ها — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-ink">مدیریت کاربران، اشتراک‌ها و امنیت</h2>
            <p class="text-xs text-broca-slate mt-1">مشاهده مشخصات فراگیران، وضعیت فعال/تعلیق، نقش مدیریت و تاریخچه آموزشی</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="surface-panel mt-6 p-4">
        <form method="get" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="جستجو بر اساس نام، ایمیل یا شماره همراه..."
                   class="flex-1 min-w-[240px] rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">

            <select name="status" class="rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">
                <option value="">همه وضعیت‌ها</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>فعال</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>تعلیق‌شده</option>
            </select>

            <select name="role" class="rounded-xl border border-ink/20 p-2.5 text-xs bg-white/70">
                <option value="">همه نقش‌ها</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>مدیران سامانه</option>
                <option value="learner" {{ request('role') === 'learner' ? 'selected' : '' }}>دانشجویان / فراگیران</option>
            </select>

            <button type="submit" class="rounded-xl bg-broca-sand px-4 py-2.5 text-xs font-bold text-ink hover:bg-ink hover:text-cream">فیلتر</button>
            @if (request()->hasAny(['q', 'status', 'role']))
                <a href="{{ route('admin.users.index') }}" class="text-xs text-coral font-bold underline">پاک کردن</a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="surface-panel mt-6 overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-ink/5 text-right text-broca-slate border-b border-broca-sand">
                <tr>
                    <th class="p-3.5 font-black">کاربر</th>
                    <th class="p-3.5 font-black">اطلاعات تماس</th>
                    <th class="p-3.5 font-black text-center">دوره‌ها</th>
                    <th class="p-3.5 font-black text-center">اشتراک‌ها</th>
                    <th class="p-3.5 font-black text-center">آزمون‌ها</th>
                    <th class="p-3.5 font-black">نقش و وضعیت</th>
                    <th class="p-3.5 font-black text-left">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-broca-sand">
                @forelse ($users as $user)
                    <tr class="hover:bg-white/40">
                        <td class="p-3.5 font-bold text-ink">
                            <div class="flex items-center gap-2">
                                <span class="grid size-8 place-items-center rounded-full bg-ink text-cream font-black text-xs">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </span>
                                <div>
                                    <span class="font-black text-sm block">{{ $user->name }}</span>
                                    <span class="text-[11px] text-broca-slate">عضویت: {{ $user->created_at?->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 text-broca-slate">
                            <p class="font-mono text-[11px]" dir="ltr">{{ $user->email }}</p>
                            <p class="font-mono text-[11px] text-ink mt-0.5" dir="ltr">{{ $user->phone ?? '—' }}</p>
                        </td>
                        <td class="p-3.5 text-center font-bold text-ink">
                            {{ $user->enrollments_count }} دوره
                        </td>
                        <td class="p-3.5 text-center font-bold">
                            <span class="rounded-full px-2 py-0.5 {{ $user->hasActiveSubscription() ? 'bg-teal/15 text-teal' : 'bg-ink/5 text-broca-slate' }}">
                                {{ $user->hasActiveSubscription() ? 'اشتراک فعال ✓' : 'رایگان' }}
                            </span>
                        </td>
                        <td class="p-3.5 text-center font-bold text-ink">
                            {{ $user->quiz_attempts_count }} آزمون
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $user->is_admin ? 'bg-coral text-cream' : 'bg-ink/10 text-ink' }}">
                                    {{ $user->is_admin ? 'مدیر ارشد' : 'دانشجو' }}
                                </span>
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $user->status === 'active' ? 'bg-teal/10 text-teal' : 'bg-coral/20 text-coral' }}">
                                    {{ $user->status === 'active' ? 'فعال' : 'تعلیق' }}
                                </span>
                            </div>
                        </td>
                        <td class="p-3.5 text-left">
                            <a href="{{ route('admin.users.show', $user) }}" class="px-3 py-1.5 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">
                                مشاهده و ویرایش دسترسی
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-sm text-broca-slate">کاربری با این مشخصات یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</section>
@endsection
