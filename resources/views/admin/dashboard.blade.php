@extends('layouts.app')

@section('title', 'پیشخوان استودیو مدیریت — ' . __('app.name'))
@section('robots', 'noindex, follow')

@section('content')
<section class="section-shell section-stack section-stack-tight-top">
    @include('admin.nav')

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">کاربران فعال</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($activeUsers) }}</p>
            <p class="mt-1 text-xs text-muted">از کل {{ number_format($users) }} حساب</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">دوره‌ها</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($courses) }}</p>
            <p class="mt-1 text-xs text-teal font-bold">{{ number_format($publishedCourses) }} مورد منتشر شده</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">رسانه و جزوه</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($videos + $notes) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $videos }} ویدیو + {{ $notes }} فایل</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft">
            <span class="text-xs font-bold text-muted">فلش‌کارت و آزمون</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($flashcards) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $decks }} دِک + {{ $quizzes }} آزمون</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft is-highlight">
            <span class="text-xs font-bold text-ink">اشتراک‌های فعال</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($activeSubscriptions) }}</p>
            <p class="mt-1 text-xs text-muted font-bold">{{ number_format($paidInvoices) }} فاکتور پرداخت‌شده</p>
        </div>
        <div class="metric-card bg-white p-5 rounded-2xl border border-hairline-soft is-success">
            <span class="text-xs font-bold text-teal">درآمد کل</span>
            <p class="mt-2 text-2xl font-black text-teal" dir="ltr">{{ number_format($revenueIrr / 10) }}</p>
            <p class="mt-1 text-xs text-teal/80 font-bold">تومان</p>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8 editorial-card is-dark">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <div class="max-w-2xl">
                    <span class="badge-on-dark">مدیریت حرفه‌ای محتوای پزشکی</span>
                    <h2 class="mt-4 text-2xl sm:text-3xl font-black text-white">انتشار محتوا، کنترل کیفیت و عملیات روزمره در یک استودیو واحد</h2>
                    <p class="mt-3 text-sm leading-8 muted-on-dark">در این پنل، هدف فقط CRUD نیست؛ باید وضعیت انتشار، کیفیت علمی، مسیر بازبینی و سلامت عملیاتی سایت برای تیم مدیریت سریع، روشن و امن باشد.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[24rem]">
                    <a href="{{ route('admin.courses.create') }}" class="button-secondary bg-white text-ink">ایجاد دوره جدید</a>
                    <a href="{{ route('admin.blogs.create') }}" class="button-secondary bg-white text-ink">ثبت مقاله جدید</a>
                    <a href="{{ route('admin.videos.create') }}" class="button-soft is-dark">افزودن ویدیو</a>
                    <a href="{{ route('admin.notes.create') }}" class="button-soft is-dark">افزودن جزوه</a>
                    <a href="{{ route('admin.flashcards.decks.create') }}" class="button-soft is-dark">ایجاد دِک فلش‌کارت</a>
                    <a href="{{ route('admin.quizzes.create') }}" class="button-soft is-dark">ایجاد آزمون</a>
                </div>
            </div>
        </div>

        <div class="xl:col-span-4 editorial-card">
            <h3 class="text-lg font-extrabold text-ink">شاخص‌های اعتماد و سلامت</h3>
            <div class="trust-list mt-5">
                <div class="trust-list-item is-soft">
                    <span class="icon-frame-soft"><x-ui.icon name="shield" class="size-5" /></span>
                    <div>
                        <strong>کنترل وضعیت انتشار</strong>
                        <span>محتوا باید بین پیش‌نویس، بازبینی، انتشار و آرشیو به‌شکل کنترل‌شده جابه‌جا شود.</span>
                    </div>
                </div>
                <div class="trust-list-item is-soft">
                    <span class="icon-frame-soft"><x-ui.icon name="wallet" class="size-5" /></span>
                    <div>
                        <strong>ردیابی عملیات مالی</strong>
                        <span>تعداد فاکتورها و درآمد ثبت‌شده باید همیشه در نگاه اول قابل بررسی باشد.</span>
                    </div>
                </div>
                <div class="trust-list-item is-soft">
                    <span class="icon-frame-soft"><x-ui.icon name="badge-check" class="size-5" /></span>
                    <div>
                        <strong>امنیت ادمین</strong>
                        <span>{{ auth()->user()->hasConfirmedTwoFactor() ? 'ورود دومرحله‌ای برای این حساب فعال است.' : 'این حساب هنوز نیازمند تکمیل تنظیمات امنیت دومرحله‌ای است.' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            <div class="editorial-card">
                <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                    <h3 class="text-base font-extrabold text-ink">آخرین دوره‌های ثبت یا به‌روزرسانی‌شده</h3>
                    <a href="{{ route('admin.courses.index') }}" class="text-xs font-bold text-rausch-text hover:underline">مشاهده همه دوره‌ها ←</a>
                </div>
                <div class="mt-4 divide-y divide-broca-sand">
                    @forelse ($recentCourses as $course)
                        <div class="py-4 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-rausch-text">{{ $course->subject->name ?? 'عمومی' }}</span>
                                <h4 class="text-sm font-extrabold text-ink mt-0.5">{{ $course->title }}</h4>
                                <p class="text-xs text-muted mt-1">نویسنده: {{ $course->author->name ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="{{ $course->status === 'published' ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $course->status === 'published' ? 'منتشر شده' : $course->status }}
                                </span>
                                <a href="{{ route('admin.courses.edit', $course) }}" class="px-4 py-2 rounded border border-ink/20 text-xs font-bold hover:bg-surface-soft transition-colors">ویرایش</a>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-muted">هنوز دوره‌ای ایجاد نشده است.</p>
                    @endforelse
                </div>
            </div>

            <div class="editorial-card">
                <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                    <h3 class="text-base font-extrabold text-ink">آخرین لاگ‌های امنیتی و عملیاتی</h3>
                    <a href="{{ route('admin.activity.index') }}" class="text-xs font-bold text-rausch-text hover:underline">تمام لاگ‌ها ←</a>
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
                                        <span class="{{ $log->status_code < 400 ? 'badge-success' : 'badge-soft' }}">{{ $log->status_code }}</span>
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

        <div class="space-y-8">
            <div class="editorial-card">
                <div class="flex items-center justify-between pb-4 border-b border-hairline-soft">
                    <h3 class="text-base font-extrabold text-ink">آخرین اشتراک‌های فعال</h3>
                    <a href="{{ route('admin.plans.index') }}" class="text-xs font-bold text-rausch-text hover:underline">مدیریت پلن‌ها ←</a>
                </div>
                <div class="mt-4 divide-y divide-broca-sand">
                    @forelse ($recentSubscriptions as $sub)
                        <div class="py-3 flex items-center justify-between text-xs gap-3">
                            <div>
                                <p class="font-extrabold text-ink">{{ $sub->user->name ?? 'کاربر' }}</p>
                                <p class="text-muted">{{ $sub->plan->name ?? 'اشتراک' }}</p>
                            </div>
                            <span class="{{ $sub->isActive() ? 'badge-success' : 'badge-neutral' }}">
                                {{ $sub->isActive() ? 'فعال' : $sub->status }}
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-muted">هنوز خریدی ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            <div class="editorial-card is-soft">
                <h3 class="text-base font-extrabold text-ink pb-4 border-b border-hairline-soft">وضعیت زیرساخت و انطباق</h3>
                <div class="mt-4 space-y-3 text-xs font-bold">
                    <div class="flex justify-between items-center py-1.5 gap-3">
                        <span class="text-muted">پایگاه داده</span>
                        <span class="text-teal">متصل و سالم</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 gap-3">
                        <span class="text-muted">درگاه پرداخت</span>
                        <span class="text-ink">زرین‌پال</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 gap-3">
                        <span class="text-muted">مدل بازبینی علمی</span>
                        <span class="text-teal">فعال</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 gap-3">
                        <span class="text-muted">امنیت دومرحله‌ای</span>
                        <span class="{{ auth()->user()->hasConfirmedTwoFactor() ? "text-teal" : "text-rausch-text" }}">{{ auth()->user()->hasConfirmedTwoFactor() ? 'فعال' : 'نیازمند پیکربندی' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
