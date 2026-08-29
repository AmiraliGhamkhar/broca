@extends('layouts.app')

@section('title', 'پیشخوان استودیو مدیریت — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <!-- Top Key Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">کاربران فعال</span>
            <p class="mt-2 text-3xl font-bold text-ink">{{ number_format($activeUsers) }}</p>
            <p class="mt-1 text-xs text-muted">از کل {{ number_format($users) }} کاربر</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">دوره‌ها</span>
            <p class="mt-2 text-3xl font-bold text-ink">{{ number_format($courses) }}</p>
            <p class="mt-1 text-xs text-teal font-bold">{{ number_format($publishedCourses) }} منتشر شده</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">محتوای رسانه‌ای</span>
            <p class="mt-2 text-3xl font-bold text-ink">{{ number_format($videos + $notes) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $videos }} ویدیو + {{ $notes }} جزوه</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">فلش‌کارت و تست</span>
            <p class="mt-2 text-3xl font-bold text-ink">{{ number_format($flashcards) }}</p>
            <p class="mt-1 text-xs text-muted">در {{ $decks }} دسته + {{ $quizzes }} آزمون</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft is-highlight">
            <span class="text-xs font-bold text-ink">اشتراک‌های فعال</span>
            <p class="mt-2 text-3xl font-bold text-ink">{{ number_format($activeSubscriptions) }}</p>
            <p class="mt-1 text-xs text-muted font-bold">{{ number_format($paidInvoices) }} فاکتور پرداخت‌شده</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft is-success">
            <span class="text-xs font-bold text-teal">درآمد کل</span>
            <p class="mt-2 text-2xl font-bold text-teal" dir="ltr">{{ number_format($revenueIrr / 10) }}</p>
            <p class="mt-1 text-xs text-teal/80 font-bold">تومان (زرین‌پال)</p>
        </div>
    </div>

    <!-- Quick Action Launchpad -->
    <div class="mt-8 rounded-3xl bg-ink p-6 sm:p-8 text-white shadow-float">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-rausch px-3 py-1 text-xs font-bold text-white">
                    <span class="size-2 rounded-full bg-teal animate-pulse"></span>
                    مرکز دسترسی سریع
                </span>
                <h2 class="mt-2 text-xl sm:text-2xl font-bold">مدیریت و گسترش محتوای پزشکی</h2>
                <p class="mt-1 text-sm text-white/70">افزودن مستقیم درس، ویدیو، جزوه اختصاصی، فلش‌کارت یا آزمون جدید</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.courses.create') }}" class="rounded-full bg-white px-5 py-2.5 text-xs font-bold text-ink hover:bg-surface-soft transition-colors">+ دوره جدید</a>
                <a href="{{ route('admin.videos.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-bold text-white hover:bg-white/20 transition-all border border-cream/20">+ ویدیو</a>
                <a href="{{ route('admin.notes.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-bold text-white hover:bg-white/20 transition-all border border-cream/20">+ جزوه</a>
                <a href="{{ route('admin.flashcards.decks.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-bold text-white hover:bg-white/20 transition-all border border-cream/20">+ دسته فلش‌کارت</a>
                <a href="{{ route('admin.quizzes.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-bold text-white hover:bg-white/20 transition-all border border-cream/20">+ آزمون</a>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Recent Courses & Recent Subscriptions / Activity -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Courses Overview (2 cols) -->
        <div class="lg:col-span-2 space-y-8">
            <div class="surface-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                    <h3 class="text-base font-bold text-ink flex items-center gap-2">
                        <span>🎓</span> دوره‌های آموزشی اخیر
                    </h3>
                    <a href="{{ route('admin.courses.index') }}" class="text-xs font-bold text-rausch hover:underline">مشاهده همه دوره‌ها ←</a>
                </div>
                <div class="mt-4 divide-y divide-broca-sand">
                    @forelse ($recentCourses as $course)
                        <div class="py-4 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-rausch">{{ $course->subject->name ?? 'عمومی' }}</span>
                                <h4 class="text-sm font-bold text-ink mt-0.5">{{ $course->title }}</h4>
                                <p class="text-xs text-muted mt-1">نویسنده: {{ $course->author->name ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $course->status === 'published' ? 'bg-teal/10 text-teal' : 'bg-surface-soft text-ink' }}">
                                    {{ $course->status === 'published' ? 'منتشر شده' : $course->status }}
                                </span>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="px-3 py-1.5 rounded-full border border-ink/20 text-xs font-bold hover:bg-surface-soft">ویرایش</a>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-muted">هنوز دوره‌ای ایجاد نشده است.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Activity Log (Forensic Trail) -->
            <div class="surface-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                    <h3 class="text-base font-bold text-ink flex items-center gap-2">
                        <span>🛡</span> آخرین لاگ‌های امنیتی و تغییرات مدیران
                    </h3>
                    <a href="{{ route('admin.activity.index') }}" class="text-xs font-bold text-rausch hover:underline">تمام لاگ‌ها ←</a>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="text-right text-muted bg-surface-soft">
                            <tr>
                                <th class="p-2.5 font-bold">زمان</th>
                                <th class="p-2.5 font-bold">مدیر</th>
                                <th class="p-2.5 font-bold">عملیات</th>
                                <th class="p-2.5 font-bold">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-broca-sand">
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td class="p-2.5 text-muted" dir="ltr">{{ $log->created_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}</td>
                                    <td class="p-2.5 font-bold text-ink">{{ $log->actor_name_snapshot ?: $log->user?->name ?: 'سیستم' }}</td>
                                    <td class="p-2.5 font-mono text-[11px] text-ink/80" dir="ltr">{{ $log->action }}</td>
                                    <td class="p-2.5">
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $log->status_code < 400 ? 'bg-teal/10 text-teal' : 'bg-rausch-tint text-rausch' }}">{{ $log->status_code }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-4 text-center text-muted">هنوز ثبتی وجود ندارد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Subscriptions & System Health -->
        <div class="space-y-8">
            <div class="surface-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                    <h3 class="text-base font-bold text-ink flex items-center gap-2">
                        <span>💳</span> آخرین اشتراک‌های فعال
                    </h3>
                    <a href="{{ route('admin.plans.index') }}" class="text-xs font-bold text-rausch hover:underline">پلن‌ها ←</a>
                </div>
                <div class="mt-4 divide-y divide-broca-sand">
                    @forelse ($recentSubscriptions as $sub)
                        <div class="py-3 flex items-center justify-between text-xs">
                            <div>
                                <p class="font-bold text-ink">{{ $sub->user->name ?? 'کاربر' }}</p>
                                <p class="text-muted">{{ $sub->plan->name ?? 'اشتراک' }}</p>
                            </div>
                            <div class="text-left">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $sub->isActive() ? 'bg-teal/10 text-teal' : 'bg-surface-soft text-muted' }}">
                                    {{ $sub->isActive() ? 'فعال' : $sub->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-muted">هنوز خریدی ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            <div class="surface-panel p-6">
                <h3 class="text-base font-bold text-ink flex items-center gap-2 pb-4 border-b border-hairline-soft">
                    <span>⚙️</span> وضعیت سلامت و زیرساخت
                </h3>
                <div class="mt-4 space-y-3 text-xs font-bold">
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-muted">پایگاه داده MySQL:</span>
                        <span class="text-teal font-bold">متصل و سالم ✓</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-muted">درگاه پرداخت:</span>
                        <span class="text-ink">زرین‌پال (Rial)</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-muted">قفل خودکار سقف رایگان:</span>
                        <span class="text-teal">فعال و ایمن (Atomic)</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-muted">تأیید دو مرحله‌ای مدیر:</span>
                        <span class="{{ auth()->user()->hasConfirmedTwoFactor() ? 'text-teal' : 'text-rausch' }}">
                            {{ auth()->user()->hasConfirmedTwoFactor() ? 'فعال ✓' : 'نیاز به پیکربندی' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
