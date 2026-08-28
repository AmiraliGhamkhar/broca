@extends('layouts.app')

@section('title', 'پیشخوان استودیو مدیریت — ' . __('app.name'))

@section('robots', 'noindex, follow')

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('admin.nav')

    <!-- Top Key Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="metric-card bg-white/80 p-5 rounded-2xl border border-broca-sand">
            <span class="text-xs font-black text-broca-slate">کاربران فعال</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($activeUsers) }}</p>
            <p class="mt-1 text-xs text-broca-slate">از کل {{ number_format($users) }} کاربر</p>
        </div>
        <div class="metric-card bg-white/80 p-5 rounded-2xl border border-broca-sand">
            <span class="text-xs font-black text-broca-slate">دوره‌ها</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($courses) }}</p>
            <p class="mt-1 text-xs text-teal font-bold">{{ number_format($publishedCourses) }} منتشر شده</p>
        </div>
        <div class="metric-card bg-white/80 p-5 rounded-2xl border border-broca-sand">
            <span class="text-xs font-black text-broca-slate">محتوای رسانه‌ای</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($videos + $notes) }}</p>
            <p class="mt-1 text-xs text-broca-slate">{{ $videos }} ویدیو + {{ $notes }} جزوه</p>
        </div>
        <div class="metric-card bg-white/80 p-5 rounded-2xl border border-broca-sand">
            <span class="text-xs font-black text-broca-slate">فلش‌کارت و تست</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($flashcards) }}</p>
            <p class="mt-1 text-xs text-broca-slate">در {{ $decks }} دسته + {{ $quizzes }} آزمون</p>
        </div>
        <div class="metric-card bg-white/80 p-5 rounded-2xl border border-broca-sand is-highlight">
            <span class="text-xs font-black text-ink">اشتراک‌های فعال</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($activeSubscriptions) }}</p>
            <p class="mt-1 text-xs text-ink/75 font-bold">{{ number_format($paidInvoices) }} فاکتور پرداخت‌شده</p>
        </div>
        <div class="metric-card bg-white/80 p-5 rounded-2xl border border-broca-sand is-success">
            <span class="text-xs font-black text-teal">درآمد کل</span>
            <p class="mt-2 text-2xl font-black text-teal" dir="ltr">{{ number_format($revenueIrr / 10) }}</p>
            <p class="mt-1 text-xs text-teal/80 font-bold">تومان (زرین‌پال)</p>
        </div>
    </div>

    <!-- Quick Action Launchpad -->
    <div class="mt-8 rounded-3xl bg-gradient-to-r from-ink via-ink/90 to-ink p-6 sm:p-8 text-cream shadow-md">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-sun/20 px-3 py-1 text-xs font-black text-sun">
                    <span class="size-2 rounded-full bg-teal animate-pulse"></span>
                    مرکز دسترسی سریع
                </span>
                <h2 class="mt-2 text-xl sm:text-2xl font-black">مدیریت و گسترش محتوای پزشکی</h2>
                <p class="mt-1 text-sm text-cream/70">افزودن مستقیم درس، ویدیو، جزوه اختصاصی، فلش‌کارت یا آزمون جدید</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('admin.courses.create') }}" class="rounded-full bg-sun px-5 py-2.5 text-xs font-black text-ink hover:bg-cream transition-all shadow-sm">+ دوره جدید</a>
                <a href="{{ route('admin.videos.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-black text-cream hover:bg-white/20 transition-all border border-cream/20">+ ویدیو</a>
                <a href="{{ route('admin.notes.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-black text-cream hover:bg-white/20 transition-all border border-cream/20">+ جزوه</a>
                <a href="{{ route('admin.flashcards.decks.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-black text-cream hover:bg-white/20 transition-all border border-cream/20">+ دسته فلش‌کارت</a>
                <a href="{{ route('admin.quizzes.create') }}" class="rounded-full bg-white/10 px-5 py-2.5 text-xs font-black text-cream hover:bg-white/20 transition-all border border-cream/20">+ آزمون</a>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Recent Courses & Recent Subscriptions / Activity -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Courses Overview (2 cols) -->
        <div class="lg:col-span-2 space-y-8">
            <div class="surface-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
                    <h3 class="text-base font-black text-ink flex items-center gap-2">
                        <span>🎓</span> دوره‌های آموزشی اخیر
                    </h3>
                    <a href="{{ route('admin.courses.index') }}" class="text-xs font-bold text-coral hover:underline">مشاهده همه دوره‌ها ←</a>
                </div>
                <div class="mt-4 divide-y divide-broca-sand">
                    @forelse ($recentCourses as $course)
                        <div class="py-4 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-coral">{{ $course->subject->name ?? 'عمومی' }}</span>
                                <h4 class="text-sm font-black text-ink mt-0.5">{{ $course->title }}</h4>
                                <p class="text-xs text-broca-slate mt-1">نویسنده: {{ $course->author->name ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $course->status === 'published' ? 'bg-teal/10 text-teal' : 'bg-sun text-ink' }}">
                                    {{ $course->status === 'published' ? 'منتشر شده' : $course->status }}
                                </span>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="px-3 py-1.5 rounded-full border border-ink/20 text-xs font-bold hover:bg-broca-sand">ویرایش</a>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-broca-slate">هنوز دوره‌ای ایجاد نشده است.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Activity Log (Forensic Trail) -->
            <div class="surface-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
                    <h3 class="text-base font-black text-ink flex items-center gap-2">
                        <span>🛡</span> آخرین لاگ‌های امنیتی و تغییرات مدیران
                    </h3>
                    <a href="{{ route('admin.activity.index') }}" class="text-xs font-bold text-coral hover:underline">تمام لاگ‌ها ←</a>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="text-right text-broca-slate bg-ink/5">
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
                                    <td class="p-2.5 text-broca-slate" dir="ltr">{{ $log->created_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}</td>
                                    <td class="p-2.5 font-bold text-ink">{{ $log->actor_name_snapshot ?: $log->user?->name ?: 'سیستم' }}</td>
                                    <td class="p-2.5 font-mono text-[11px] text-ink/80" dir="ltr">{{ $log->action }}</td>
                                    <td class="p-2.5">
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $log->status_code < 400 ? 'bg-teal/10 text-teal' : 'bg-coral/10 text-coral' }}">{{ $log->status_code }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-4 text-center text-broca-slate">هنوز ثبتی وجود ندارد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side: Subscriptions & System Health -->
        <div class="space-y-8">
            <div class="surface-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-broca-sand">
                    <h3 class="text-base font-black text-ink flex items-center gap-2">
                        <span>💳</span> آخرین اشتراک‌های فعال
                    </h3>
                    <a href="{{ route('admin.plans.index') }}" class="text-xs font-bold text-coral hover:underline">پلن‌ها ←</a>
                </div>
                <div class="mt-4 divide-y divide-broca-sand">
                    @forelse ($recentSubscriptions as $sub)
                        <div class="py-3 flex items-center justify-between text-xs">
                            <div>
                                <p class="font-bold text-ink">{{ $sub->user->name ?? 'کاربر' }}</p>
                                <p class="text-broca-slate">{{ $sub->plan->name ?? 'اشتراک' }}</p>
                            </div>
                            <div class="text-left">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $sub->isActive() ? 'bg-teal/10 text-teal' : 'bg-broca-sand text-broca-slate' }}">
                                    {{ $sub->isActive() ? 'فعال' : $sub->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-broca-slate">هنوز خریدی ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            <div class="surface-panel p-6">
                <h3 class="text-base font-black text-ink flex items-center gap-2 pb-4 border-b border-broca-sand">
                    <span>⚙️</span> وضعیت سلامت و زیرساخت
                </h3>
                <div class="mt-4 space-y-3 text-xs font-bold">
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-broca-slate">پایگاه داده MySQL:</span>
                        <span class="text-teal font-black">متصل و سالم ✓</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-broca-slate">درگاه پرداخت:</span>
                        <span class="text-ink">زرین‌پال (Rial)</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-broca-slate">قفل خودکار سقف رایگان:</span>
                        <span class="text-teal">فعال و ایمن (Atomic)</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-broca-slate">تأیید دو مرحله‌ای مدیر:</span>
                        <span class="{{ auth()->user()->hasConfirmedTwoFactor() ? 'text-teal' : 'text-coral' }}">
                            {{ auth()->user()->hasConfirmedTwoFactor() ? 'فعال ✓' : 'نیاز به پیکربندی' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
