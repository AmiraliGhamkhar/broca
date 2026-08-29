@extends('layouts.app')

@section('title', 'داشبورد یادگیری — ' . __('app.name'))

@section('content')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ activeTab: 'courses' }">

    <!-- Top Greeting & Subscription Status Banner -->
    <div class="rounded-3xl bg-ink p-6 sm:p-8 text-white shadow-float">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <span class="grid size-12 place-items-center rounded-2xl bg-rausch text-white text-xl">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                    <div>
                        <h1 class="font-display text-2xl sm:text-3xl">سلام، {{ $user->name }}</h1>
                        <p class="text-xs text-white/70 mt-0.5">به استودیوی یادگیری پزشکی بروکا خوش آمدید.</p>
                    </div>
                </div>
            </div>

            <!-- Subscription Badge & Upgrade Action -->
            <div class="flex items-center gap-3">
                @if ($hasSubscription && $activeSubscription)
                    <div class="bg-teal/20 border border-teal/40 rounded-2xl px-5 py-3 text-right">
                        <span class="text-[11px] text-teal font-bold block">پلن اشتراک شما:</span>
                        <p class="text-sm font-bold text-white">{{ $activeSubscription->plan->name ?? 'اشتراک ویژه' }}</p>
                        <span class="text-[10px] text-white/60 block mt-0.5" dir="ltr">
                            انقضا: {{ $activeSubscription->ends_at ? $activeSubscription->ends_at->timezone(config('broca.display_timezone'))->format('Y/m/d') : 'نامحدود' }}
                        </span>
                    </div>
                @else
                    <div class="bg-rausch-tint border border-rausch/30 rounded-2xl px-5 py-3 text-right">
                        <span class="text-[11px] text-ink font-bold block">وضعیت حساب:</span>
                        <p class="text-sm font-bold text-ink">حساب رایگان (دسترسی محدود)</p>
                        <a href="{{ route('plans') }}" class="inline-block mt-1 text-xs text-rausch font-bold underline hover:text-rausch-active">
                            ارتقا به اشتراک ویژه ←
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Analytics & Learning Metrics Grid -->
    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="metric-card p-5">
            <span class="text-xs font-bold text-muted">دوره‌های فعال من</span>
            <p class="mt-2 font-display text-3xl text-ink">{{ number_format($enrollments->count()) }}</p>
            <p class="mt-1 text-xs text-muted">دوره در حال یادگیری</p>
        </div>

        <div class="metric-card p-5 is-highlight">
            <span class="text-xs font-bold text-ink">کارت‌های سررسید امروز</span>
            <p class="mt-2 font-display text-3xl text-ink">{{ number_format($dueFlashcardsCount) }}</p>
            <p class="mt-1 text-xs text-ink/75 font-bold">نیاز به مرور فاصله‌دار</p>
        </div>

        <div class="metric-card p-5">
            <span class="text-xs font-bold text-muted">ویدیوهای تکمیل‌شده</span>
            <p class="mt-2 font-display text-3xl text-ink">{{ number_format($completedVideos) }}</p>
            <p class="mt-1 text-xs text-teal font-bold">مطابق آستانه مشاهده</p>
        </div>

        <div class="metric-card p-5 is-success">
            <span class="text-xs font-bold text-teal">مرورهای ثبت‌شده (SM-2)</span>
            <p class="mt-2 text-3xl font-bold text-teal">{{ number_format($totalReviewsCount) }}</p>
            <p class="mt-1 text-xs text-teal/80 font-bold">تکرار موفق در حافظه</p>
        </div>
    </div>

    <!-- Continue Learning Banner (Last Watched Lesson) -->
    @if ($recentProgress->isNotEmpty())
        @php $latest = $recentProgress->first(); @endphp
        @if ($latest && $latest->video)
            <div class="mt-8 rounded-2xl bg-rausch-tint border border-rausch/20 p-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-ink text-white text-lg">▶</span>
                    <div>
                        <span class="text-[11px] font-bold text-rausch">ادامه یادگیری آخرین درس:</span>
                        <h2 class="text-sm font-bold text-ink mt-0.5">{{ $latest->video->title }}</h2>
                        <p class="text-xs text-muted">{{ $latest->video->course->title ?? '' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <span class="text-xs font-bold text-ink">{{ $latest->watched_percent }}٪ مشاهده شده</span>
                        <div class="w-32 h-1.5 rounded-full bg-ink/10 overflow-hidden mt-1">
                            <div class="h-full bg-rausch rounded-full" style="width: {{ $latest->watched_percent }}%"></div>
                        </div>
                    </div>
                    <a href="{{ route('videos.show', [$latest->video->course, $latest->video]) }}"
                       class="px-5 py-2.5 rounded-full bg-rausch text-white text-xs font-bold hover:bg-rausch-active transition-all">
                        ادامه پخش ویدیو ←
                    </a>
                </div>
            </div>
        @endif
    @endif

    <!-- Workspace Navigation Tabs -->
    <div class="mt-10 border-b border-hairline-soft">
        <nav class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-none" aria-label="بخش‌های آموزشی">
            <button type="button" @click="activeTab = 'courses'"
                    :class="activeTab === 'courses' ? 'bg-rausch text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft'"
                    class="px-5 py-2.5 rounded-full text-xs font-bold transition-all">
                🎓 دوره‌های من ({{ $enrollments->count() }})
            </button>
            <button type="button" @click="activeTab = 'flashcards'"
                    :class="activeTab === 'flashcards' ? 'bg-rausch text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft'"
                    class="px-5 py-2.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5">
                <span>🗂 مرور هوشمند کارت‌ها</span>
                @if ($dueFlashcardsCount > 0)
                    <span class="size-2 rounded-full bg-rausch animate-ping"></span>
                @endif
            </button>
            <button type="button" @click="activeTab = 'quizzes'"
                    :class="activeTab === 'quizzes' ? 'bg-rausch text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft'"
                    class="px-5 py-2.5 rounded-full text-xs font-bold transition-all">
                📝 آزمون‌ها و نتایج
            </button>
            <button type="button" @click="activeTab = 'discover'"
                    :class="activeTab === 'discover' ? 'bg-rausch text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft'"
                    class="px-5 py-2.5 rounded-full text-xs font-bold transition-all">
                🔍 کشف دوره‌های جدید
            </button>
        </nav>
    </div>

    <!-- TAB 1: MY ENROLLED COURSES & EXPANDED CONTENT -->
    <div x-show="activeTab === 'courses'" class="mt-8 space-y-8">
        @forelse ($enrollments as $enrollment)
            @php $course = $enrollment->course; @endphp
            <div class="surface-panel p-6 sm:p-8 transition-all hover:shadow-md">
                <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                    <div class="space-y-1">
                        <span class="text-xs font-bold text-rausch">{{ $course->subject->name ?? 'علوم پایه پزشکی' }}</span>
                        <h2 class="text-xl sm:text-2xl font-bold text-ink">{{ $course->title }}</h2>
                        <p class="text-xs text-muted leading-5 max-w-2xl">{{ $course->excerpt ?: $course->description }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('courses.show', $course) }}" class="px-4 py-2 rounded-full border border-ink/20 text-xs font-bold hover:bg-broca-sand">
                            صفحه کامل دوره ←
                        </a>
                    </div>
                </div>

                <!-- Course Content Modules (Videos, Notes, Decks, Quizzes) -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <!-- Module 1: Video Lessons -->
                    <div class="p-4 rounded-2xl bg-white/70 border border-hairline-soft space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-hairline-soft">
                            <span class="text-xs font-bold text-ink flex items-center gap-1.5">
                                <span>🎥</span> ویدیوهای درس
                            </span>
                            <span class="text-[11px] text-muted font-bold">{{ $course->videos->count() }} درس</span>
                        </div>
                        <ul class="space-y-2 text-xs">
                            @forelse ($course->videos as $v)
                                <li class="p-2 rounded-xl bg-ink/5 hover:bg-surface-soft transition-colors">
                                    <a href="{{ route('videos.show', [$course, $v]) }}" class="flex items-center justify-between font-bold text-ink">
                                        <span class="truncate">{{ $v->title }}</span>
                                        <span class="text-[10px] text-rausch shrink-0 mr-1">{{ $v->is_free_available ? 'رایگان' : 'ویژه' }}</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز ویدیویی منتشر نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Module 2: Notes & Handouts -->
                    <div class="p-4 rounded-2xl bg-white/70 border border-hairline-soft space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-hairline-soft">
                            <span class="text-xs font-bold text-ink flex items-center gap-1.5">
                                <span>📄</span> جزوات و PDFها
                            </span>
                            <span class="text-[11px] text-muted font-bold">{{ $course->notes->count() }} جزوه</span>
                        </div>
                        <ul class="space-y-2 text-xs">
                            @forelse ($course->notes as $n)
                                <li class="p-2 rounded-xl bg-ink/5 hover:bg-surface-soft transition-colors">
                                    <a href="{{ route('notes.show', [$course, $n]) }}" class="flex items-center justify-between font-bold text-ink">
                                        <span class="truncate">{{ $n->title }}</span>
                                        <span class="text-[10px] text-teal shrink-0 mr-1">دانلود PDF</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز جزوه‌ای منتشر نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Module 3: Flashcard Decks -->
                    <div class="p-4 rounded-2xl bg-white/70 border border-hairline-soft space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-hairline-soft">
                            <span class="text-xs font-bold text-ink flex items-center gap-1.5">
                                <span>🗂</span> کارت‌های مرور
                            </span>
                            <span class="text-[11px] text-muted font-bold">{{ $course->decks->count() }} دسته</span>
                        </div>
                        <ul class="space-y-2 text-xs">
                            @forelse ($course->decks as $d)
                                <li class="p-2 rounded-xl bg-ink/5 hover:bg-surface-soft transition-colors">
                                    <a href="{{ route('decks.study', [$course, $d]) }}" class="flex items-center justify-between font-bold text-ink">
                                        <span class="truncate">{{ $d->title }}</span>
                                        <span class="text-[10px] text-rausch shrink-0 mr-1">{{ $d->cards_count }} کارت</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز دسته‌ای ساخته نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Module 4: Quizzes -->
                    <div class="p-4 rounded-2xl bg-white/70 border border-hairline-soft space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-hairline-soft">
                            <span class="text-xs font-bold text-ink flex items-center gap-1.5">
                                <span>📝</span> آزمون‌های تشخیصی
                            </span>
                            <span class="text-[11px] text-muted font-bold">{{ $course->quizzes->count() }} آزمون</span>
                        </div>
                        <ul class="space-y-2 text-xs">
                            @forelse ($course->quizzes as $q)
                                <li class="p-2 rounded-xl bg-ink/5 hover:bg-surface-soft transition-colors">
                                    <a href="{{ route('quizzes.show', $q) }}" class="flex items-center justify-between font-bold text-ink">
                                        <span class="truncate">{{ $q->title }}</span>
                                        <span class="text-[10px] text-teal shrink-0 mr-1">شروع آزمون</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز آزمونی ثبت نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                </div>
            </div>
        @empty
            <div class="empty-state p-12 text-center">
                <span class="text-4xl block mb-3">🎓</span>
                <h3 class="text-lg font-bold text-ink">هنوز در دوره‌ای ثبت‌نام نکرده‌اید</h3>
                <p class="text-xs text-muted mt-1 max-w-md mx-auto">
                    برای شروع یادگیری، به تب «کشف دوره‌ها» بروید یا کاتالوگ دوره‌های پزشکی را مرور کنید.
                </p>
                <button type="button" @click="activeTab = 'discover'" class="mt-4 px-6 py-2.5 rounded-full bg-rausch text-white text-xs font-bold hover:bg-rausch-active">
                    مشاهده دوره‌های پیشنهادی
                </button>
            </div>
        @endforelse
    </div>

    <!-- TAB 2: FLASHCARD SPACED REPETITION (SM-2) -->
    <div x-show="activeTab === 'flashcards'" x-cloak class="mt-8 space-y-6">
        <div class="surface-panel p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-hairline-soft">
                <div>
                    <h2 class="text-xl font-bold text-ink">برنامه مرور فاصله‌دار (Spaced Repetition)</h2>
                    <p class="text-xs text-muted mt-1">الگوریتم SM-2 زمان مرور بعدی هر کارت را بر اساس کیفیت یادآوری شما تنظیم می‌کند.</p>
                </div>
                <span class="rounded-full px-3 py-1 bg-surface-soft text-xs font-bold text-ink">
                    {{ $dueFlashcardsCount }} کارت آماده مرور امروز
                </span>
            </div>

            <!-- Due Flashcards Queue -->
            <div class="mt-6 space-y-4">
                @forelse ($dueSchedules as $sched)
                    @php $card = $sched->flashcard; @endphp
                    @if ($card && $card->deck && $card->deck->course)
                        <div class="p-5 rounded-2xl bg-white/70 border border-hairline-soft flex flex-wrap items-center justify-between gap-4">
                            <div class="space-y-1 max-w-xl">
                                <span class="text-[11px] font-bold text-rausch">{{ $card->deck->course->title }} · {{ $card->deck->title }}</span>
                                <h3 class="text-sm font-bold text-ink">{{ $card->front }}</h3>
                                @if ($card->hint)
                                    <p class="text-xs text-muted">راهنما: {{ $card->hint }}</p>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('decks.study', [$card->deck->course, $card->deck]) }}"
                                   class="px-5 py-2.5 rounded-full bg-rausch text-white text-xs font-bold hover:bg-rausch-active transition-all">
                                    مرور در دسته کارت ←
                                </a>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="py-12 text-center text-muted">
                        <span class="text-3xl block mb-2">🎉</span>
                        <h3 class="text-base font-bold text-ink">تمامی کارت‌های امروز مرور شده‌اند!</h3>
                        <p class="text-xs mt-1">کارت‌های بعدی در موعد مقرر بر اساس الگوریتم حافظه نمایش داده خواهند شد.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- TAB 3: QUIZZES & RESULTS -->
    <div x-show="activeTab === 'quizzes'" x-cloak class="mt-8 space-y-6">
        <div class="surface-panel p-6 sm:p-8">
            <h2 class="text-xl font-bold text-ink pb-4 border-b border-hairline-soft">تاریخچه و کارنامه آزمون‌ها</h2>

            <div class="mt-6 divide-y divide-broca-sand">
                @forelse ($recentAttempts as $attempt)
                    <div class="py-4 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div>
                            <span class="text-muted block">{{ $attempt->quiz->course->title ?? '' }}</span>
                            <h3 class="text-sm font-bold text-ink mt-0.5">{{ $attempt->quiz->title ?? 'آزمون' }}</h3>
                            <span class="text-[11px] text-muted block mt-1">
                                تاریخ: {{ $attempt->submitted_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-left">
                                <span class="text-base font-bold {{ $attempt->passed ? 'text-teal' : 'text-rausch' }}">
                                    {{ $attempt->score_percent }}٪
                                </span>
                                <span class="text-[11px] block font-bold {{ $attempt->passed ? 'text-teal' : 'text-rausch' }}">
                                    {{ $attempt->passed ? 'قبول شده ✓' : 'نیاز به تمرین' }} ({{ $attempt->correct_count }} از {{ $attempt->question_count }})
                                </span>
                            </div>
                            <a href="{{ route('quizzes.attempts.show', [$attempt->quiz, $attempt]) }}"
                               class="px-4 py-2 rounded-full border border-ink/20 font-bold hover:bg-broca-sand">
                                کارنامه تشریحی
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-xs text-muted">هنوز در آزمونی شرکت نکرده‌اید.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- TAB 4: DISCOVER COURSES -->
    <div x-show="activeTab === 'discover'" x-cloak class="mt-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-ink">دوره‌های آموزشی پیشنهادی</h2>
                <p class="text-xs text-muted mt-1">با یک کلیک در دوره‌های جدید ثبت‌نام کنید و به محتوای آموزشی دسترسی یابید.</p>
            </div>
            <a href="{{ route('catalog') }}" class="text-xs font-bold text-rausch underline">مشاهده کاتالوگ کامل ←</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            @forelse ($availableCourses as $c)
                <div class="interactive-card surface-panel p-6 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-rausch">{{ $c->subject->name ?? 'عمومی' }}</span>
                        <h3 class="text-lg font-bold text-ink mt-1">{{ $c->title }}</h3>
                        <p class="text-xs text-muted mt-2 leading-5">{{ $c->excerpt ?: $c->description }}</p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-hairline-soft flex items-center justify-between">
                        <span class="text-xs text-muted font-bold">استاد: {{ $c->author->name ?? '—' }}</span>
                        <form method="post" action="{{ route('courses.enroll', $c) }}">
                            @csrf
                            <button type="submit" class="px-5 py-2 rounded-full bg-rausch text-white text-xs font-bold hover:bg-rausch-active transition-all">
                                ثبت‌نام رایگان در دوره
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="p-8 text-center text-xs text-muted col-span-2">تمامی دوره‌های موجود ثبت‌نام شده‌اند.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
