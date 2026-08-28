@extends('layouts.app')

@section('title', 'پروفایل و دسترسی کاربر: ' . $user->name . ' — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-broca-sand">
        <div>
            <span class="text-xs font-bold text-coral">شناسه کاربر: #{{ $user->id }}</span>
            <h2 class="text-2xl font-black text-ink mt-0.5">{{ $user->name }}</h2>
            <p class="text-xs text-broca-slate mt-1">{{ $user->email }} · {{ $user->phone ?? 'بدون شماره' }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-coral underline">بازگشت به لیست کاربران</a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-2xl border border-coral/40 bg-coral/10 p-4 text-xs font-bold text-coral" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- User Edit Panel -->
        <div class="form-panel md:col-span-1 space-y-6">
            <h3 class="text-sm font-black text-ink pb-2 border-b border-broca-sand">تنظیم دسترسی و وضعیت حساب</h3>

            <form method="post" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf @method('patch')

                <div>
                    <label for="status" class="block text-xs font-black text-ink">وضعیت حساب</label>
                    <select id="status" name="status" class="w-full mt-1.5 p-2.5 rounded-xl border border-ink/20 text-xs font-bold bg-white/70">
                        <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>فعال (دسترسی عادی)</option>
                        <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>تعلیق‌شده (خروج فوری و مسدودسازی)</option>
                    </select>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }} class="rounded border-ink/20 size-4">
                        <span class="text-xs font-bold text-ink">نقش مدیر ارشد سامانه (Admin)</span>
                    </label>
                </div>

                <button type="submit" class="w-full mt-4 rounded-full bg-ink py-2.5 text-xs font-black text-cream hover:bg-coral transition-all">
                    ذخیره تغییرات دسترسی
                </button>
            </form>

            <div class="pt-4 border-t border-broca-sand text-xs space-y-2 text-broca-slate">
                <p><span class="font-bold text-ink">تأیید ایمیل:</span> {{ $user->email_verified_at ? 'تأیید شده در ' . $user->email_verified_at->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') : 'تأیید نشده' }}</p>
                <p><span class="font-bold text-ink">احراز هویت دو مرحله‌ای:</span> {{ $user->hasConfirmedTwoFactor() ? 'فعال ✓' : 'غیرفعال' }}</p>
            </div>
        </div>

        <!-- User Learning History & Subscriptions -->
        <div class="md:col-span-2 space-y-6">
            <!-- Enrolled Courses -->
            <div class="surface-panel p-6">
                <h3 class="text-sm font-black text-ink flex items-center justify-between pb-3 border-b border-broca-sand">
                    <span>🎓 دوره‌های ثبت‌نام شده</span>
                    <span class="text-xs text-broca-slate">{{ $user->enrollments->count() }} دوره</span>
                </h3>
                <div class="mt-3 divide-y divide-broca-sand text-xs">
                    @forelse ($user->enrollments as $enrollment)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-ink">{{ $enrollment->course->title ?? '—' }}</span>
                                <span class="text-[11px] text-broca-slate block">{{ $enrollment->course->subject->name ?? '' }}</span>
                            </div>
                            <span class="text-broca-slate" dir="ltr">{{ $enrollment->enrolled_at?->timezone(config('broca.display_timezone'))->format('Y/m/d') }}</span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-broca-slate">در دوره‌ای ثبت‌نام نکرده است.</p>
                    @endforelse
                </div>
            </div>

            <!-- Subscriptions & Invoices -->
            <div class="surface-panel p-6">
                <h3 class="text-sm font-black text-ink flex items-center justify-between pb-3 border-b border-broca-sand">
                    <span>💳 اشتراک‌ها و تراکنش‌ها</span>
                    <span class="text-xs text-broca-slate">{{ $user->subscriptions->count() }} اشتراک</span>
                </h3>
                <div class="mt-3 divide-y divide-broca-sand text-xs">
                    @forelse ($user->subscriptions as $sub)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-ink">{{ $sub->plan->name ?? 'پلن' }}</span>
                                <span class="text-[11px] text-broca-slate block">انقضا: {{ $sub->ends_at ? $sub->ends_at->timezone(config('broca.display_timezone'))->format('Y/m/d') : 'نامحدود' }}</span>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $sub->isActive() ? 'bg-teal/15 text-teal' : 'bg-broca-sand text-broca-slate' }}">
                                {{ $sub->isActive() ? 'فعال' : $sub->status }}
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-broca-slate">اشتراکی خریداری نشده است.</p>
                    @endforelse
                </div>
            </div>

            <!-- Quiz Attempts -->
            <div class="surface-panel p-6">
                <h3 class="text-sm font-black text-ink flex items-center justify-between pb-3 border-b border-broca-sand">
                    <span>📝 آزمون‌های گذرانده‌شده</span>
                    <span class="text-xs text-broca-slate">{{ $user->quizAttempts->count() }} تلاش</span>
                </h3>
                <div class="mt-3 divide-y divide-broca-sand text-xs">
                    @forelse ($user->quizAttempts as $attempt)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-ink">{{ $attempt->quiz->title ?? 'آزمون' }}</span>
                                <span class="text-[11px] text-broca-slate block">{{ $attempt->correct_count }} از {{ $attempt->question_count }} پاسخ درست</span>
                            </div>
                            <span class="font-bold {{ $attempt->passed ? 'text-teal' : 'text-coral' }}">
                                {{ $attempt->score_percent }}٪ ({{ $attempt->passed ? 'قبول' : 'مردود' }})
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-broca-slate">آزمونی ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
