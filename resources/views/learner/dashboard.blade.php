@extends('layouts.app')

@section('title', 'داشبورد یادگیری — ' . __('app.name'))

@section('content')
<section class="section-shell section-stack section-stack-tight-top" x-data="{ activeTab: 'courses' }">
    <div class="editorial-card is-dark">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="space-y-3 max-w-3xl">
                <div class="flex items-center gap-3">
                    <span class="icon-frame-ghost icon-frame-lg icon-frame-round text-sm font-black">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                    <div>
                        <p class="text-[11px] font-bold text-white/60">داشبورد شخصی یادگیری</p>
                        <h1 class="text-2xl sm:text-3xl font-black text-white">سلام، {{ $user->name }}</h1>
                    </div>
                </div>
                <p class="text-sm leading-8 text-white/70">در اینجا باید دقیقاً بدانید چه چیزی را شروع کنید، چه چیزی را ادامه دهید و کدام بخش از مسیر یادگیری امروز مهم‌تر است.</p>
            </div>

            <div class="meta-card is-dark min-w-[17rem]">
                @if ($hasSubscription && $activeSubscription)
                    <p class="text-[11px] font-bold text-white/60">اشتراک فعال</p>
                    <p class="mt-1 text-base font-extrabold text-white">{{ $activeSubscription->plan->name ?? 'اشتراک ویژه' }}</p>
                    <p class="mt-2 text-xs text-white/70" dir="ltr">انقضا: {{ $activeSubscription->ends_at ? $activeSubscription->ends_at->timezone(config('broca.display_timezone'))->format('Y/m/d') : 'نامحدود' }}</p>
                @else
                    <p class="text-[11px] font-bold text-white/60">وضعیت دسترسی</p>
                    <p class="mt-1 text-base font-extrabold text-white">حساب رایگان</p>
                    <p class="mt-2 text-xs text-white/70 leading-6">برای دسترسی گسترده‌تر به ویدیوها، جزوات و آزمون‌ها می‌توانید پلن‌ها را بررسی کنید.</p>
                    <a href="{{ route('plans') }}" class="button-secondary mt-4 bg-white text-ink">بررسی پلن‌های اشتراک</a>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="metric-card p-5">
            <span class="text-xs font-bold text-muted">دوره‌های فعال من</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($enrollments->count()) }}</p>
            <p class="mt-1 text-xs text-muted">مسیری که هم‌اکنون در آن‌ها یاد می‌گیرید</p>
        </div>
        <div class="metric-card p-5 is-highlight">
            <span class="text-xs font-bold text-ink">کارت‌های سررسید امروز</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($dueFlashcardsCount) }}</p>
            <p class="mt-1 text-xs text-ink/75 font-bold">مرورهای ضروری امروز</p>
        </div>
        <div class="metric-card p-5">
            <span class="text-xs font-bold text-muted">ویدیوهای تکمیل‌شده</span>
            <p class="mt-2 text-3xl font-black text-ink">{{ number_format($completedVideos) }}</p>
            <p class="mt-1 text-xs text-teal font-bold">بر اساس آستانه مشاهده</p>
        </div>
        <div class="metric-card p-5 is-success">
            <span class="text-xs font-bold text-teal">مرورهای ثبت‌شده</span>
            <p class="mt-2 text-3xl font-black text-teal">{{ number_format($totalReviewsCount) }}</p>
            <p class="mt-1 text-xs text-teal/80 font-bold">ثبت‌شده در مدل SM-2</p>
        </div>
    </div>

    @if ($recentProgress->isNotEmpty())
        @php $latest = $recentProgress->first(); @endphp
        @if ($latest && $latest->video)
            <div class="mt-8 trust-banner">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="icon-frame"><x-ui.icon name="play" class="size-5" /></span>
                        <div>
                            <span class="text-[11px] font-bold text-rausch">ادامهٔ تماشا</span>
                            <h2 class="mt-1 text-sm font-extrabold text-ink">{{ $latest->video->title }}</h2>
                            <p class="text-xs text-muted mt-1">{{ $latest->video->course->title ?? '' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-xs font-bold text-ink">{{ $latest->watched_percent }}٪ دیده‌شده</span>
                            <div class="w-36 h-1.5 rounded-full bg-ink/10 overflow-hidden mt-1">
                                <div class="h-full bg-rausch rounded-full" style="width: {{ $latest->watched_percent }}%"></div>
                            </div>
                        </div>
                        <a href="{{ route('videos.show', [$latest->video->course, $latest->video]) }}" class="button-primary">ادامه یادگیری</a>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="mt-10 border-b border-hairline-soft">
        <nav class="flex items-center gap-2 overflow-x-auto pb-3 scrollbar-none" aria-label="بخش‌های آموزشی">
            <button type="button" @click="activeTab = 'courses'"
                    :class="activeTab === 'courses' ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft border border-hairline-soft'"
                    class="px-5 py-2.5 rounded text-xs font-bold transition-all">
                دوره‌های من
            </button>
            <button type="button" @click="activeTab = 'flashcards'"
                    :class="activeTab === 'flashcards' ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft border border-hairline-soft'"
                    class="px-5 py-2.5 rounded text-xs font-bold transition-all flex items-center gap-2">
                مرور کارت‌ها
                @if ($dueFlashcardsCount > 0)
                    <span class="size-2 rounded-full bg-rausch"></span>
                @endif
            </button>
            <button type="button" @click="activeTab = 'quizzes'"
                    :class="activeTab === 'quizzes' ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft border border-hairline-soft'"
                    class="px-5 py-2.5 rounded text-xs font-bold transition-all">
                آزمون‌ها
            </button>
            <button type="button" @click="activeTab = 'discover'"
                    :class="activeTab === 'discover' ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/80 hover:bg-surface-soft border border-hairline-soft'"
                    class="px-5 py-2.5 rounded text-xs font-bold transition-all">
                کشف دوره‌های جدید
            </button>
        </nav>
    </div>

    <div x-show="activeTab === 'courses'"
 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
         class="mt-8 space-y-8">
        @forelse ($enrollments as $enrollment)
            @php $course = $enrollment->course; @endphp
            <div class="editorial-card">
                <div class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-hairline-soft">
                    <div class="space-y-2 max-w-2xl">
                        <span class="badge-soft">{{ $course->subject->name ?? 'علوم پایه پزشکی' }}</span>
                        <h2 class="text-xl sm:text-2xl font-black text-ink">{{ $course->title }}</h2>
                        <p class="text-sm text-muted leading-7">{{ $course->excerpt ?: $course->description }}</p>
                    </div>
                    <a href="{{ route('courses.show', $course) }}" class="button-secondary">صفحه کامل دوره</a>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="feature-card">
                        <span class="icon-frame-soft mb-4"><x-ui.icon name="play" class="size-5" /></span>
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-hairline-soft">
                            <strong class="text-sm font-extrabold text-ink">ویدیوهای درس</strong>
                            <span class="text-[11px] text-muted">{{ $course->videos->count() }} مورد</span>
                        </div>
                        <ul class="mt-3 space-y-2 text-xs">
                            @forelse ($course->videos as $v)
                                <li class="list-row-card bg-surface-soft border-transparent p-2.5">
                                    <a href="{{ route('videos.show', [$course, $v]) }}" class="flex items-center justify-between gap-3 font-bold text-ink">
                                        <span class="truncate">{{ $v->title }}</span>
                                        <span class="text-[11px] text-rausch shrink-0">{{ $v->is_free_available ? 'رایگان' : 'ویژه' }}</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز ویدیویی منتشر نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="feature-card">
                        <span class="icon-frame-soft mb-4"><x-ui.icon name="document" class="size-5" /></span>
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-hairline-soft">
                            <strong class="text-sm font-extrabold text-ink">جزوات و PDFها</strong>
                            <span class="text-[11px] text-muted">{{ $course->notes->count() }} مورد</span>
                        </div>
                        <ul class="mt-3 space-y-2 text-xs">
                            @forelse ($course->notes as $n)
                                <li class="list-row-card bg-surface-soft border-transparent p-2.5">
                                    <a href="{{ route('notes.show', [$course, $n]) }}" class="flex items-center justify-between gap-3 font-bold text-ink">
                                        <span class="truncate">{{ $n->title }}</span>
                                        <span class="text-[11px] text-teal shrink-0">دریافت</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز جزوه‌ای منتشر نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="feature-card">
                        <span class="icon-frame-soft mb-4"><x-ui.icon name="stack" class="size-5" /></span>
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-hairline-soft">
                            <strong class="text-sm font-extrabold text-ink">کارت‌های مرور</strong>
                            <span class="text-[11px] text-muted">{{ $course->decks->count() }} دِک</span>
                        </div>
                        <ul class="mt-3 space-y-2 text-xs">
                            @forelse ($course->decks as $d)
                                <li class="list-row-card bg-surface-soft border-transparent p-2.5">
                                    <a href="{{ route('decks.study', [$course, $d]) }}" class="flex items-center justify-between gap-3 font-bold text-ink">
                                        <span class="truncate">{{ $d->title }}</span>
                                        <span class="text-[11px] text-rausch shrink-0">{{ $d->cards_count }} کارت</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-xs text-muted py-2">هنوز دسته‌ای ساخته نشده.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="feature-card">
                        <span class="icon-frame-soft mb-4"><x-ui.icon name="quiz" class="size-5" /></span>
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-hairline-soft">
                            <strong class="text-sm font-extrabold text-ink">آزمون‌ها</strong>
                            <span class="text-[11px] text-muted">{{ $course->quizzes->count() }} مورد</span>
                        </div>
                        <ul class="mt-3 space-y-2 text-xs">
                            @forelse ($course->quizzes as $q)
                                <li class="list-row-card bg-surface-soft border-transparent p-2.5">
                                    <a href="{{ route('quizzes.show', $q) }}" class="flex items-center justify-between gap-3 font-bold text-ink">
                                        <span class="truncate">{{ $q->title }}</span>
                                        <span class="text-[11px] text-teal shrink-0">شروع</span>
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
            <div class="empty-state text-center">
                <span class="icon-frame mx-auto mb-4"><x-ui.icon name="graduation" class="size-5" /></span>
                <h3 class="text-lg font-extrabold text-ink">هنوز در دوره‌ای ثبت‌نام نکرده‌اید</h3>
                <p class="text-xs text-muted mt-2 max-w-md mx-auto leading-7">برای شروع یادگیری، از بخش کشف دوره‌ها وارد شوید و اولین مسیر آموزشی متناسب با نیازتان را انتخاب کنید.</p>
                <button type="button" @click="activeTab = 'discover'" class="button-primary mt-5">مشاهده دوره‌های پیشنهادی</button>
            </div>
        @endforelse
    </div>

    <div x-show="activeTab === 'flashcards'" x-cloak
 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
         class="mt-8">
        <div class="editorial-card">
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-hairline-soft">
                <div>
                    <h2 class="text-xl font-black text-ink">مرور فاصله‌دار امروز</h2>
                    <p class="text-xs text-muted mt-1">کارت‌ها بر اساس الگوریتم SM-2 برای مرور مؤثر و پایدار زمان‌بندی می‌شوند.</p>
                </div>
                <span class="badge-neutral">{{ $dueFlashcardsCount }} کارت آماده مرور</span>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($dueSchedules as $sched)
                    @php $card = $sched->flashcard; @endphp
                    @if ($card && $card->deck && $card->deck->course)
                        <div class="list-row-card p-5 flex flex-wrap items-center justify-between gap-4">
                            <div class="space-y-1 max-w-xl">
                                <span class="text-[11px] font-bold text-rausch">{{ $card->deck->course->title }} · {{ $card->deck->title }}</span>
                                <h3 class="text-sm font-extrabold text-ink">{{ $card->front }}</h3>
                                @if ($card->hint)
                                    <p class="text-xs text-muted">راهنما: {{ $card->hint }}</p>
                                @endif
                            </div>
                            <a href="{{ route('decks.study', [$card->deck->course, $card->deck]) }}" class="button-primary">ورود به مرور</a>
                        </div>
                    @endif
                @empty
                    <div class="py-10 text-center text-muted">
                        <span class="icon-frame mx-auto mb-4"><x-ui.icon name="badge-check" class="size-5" /></span>
                        <h3 class="text-base font-extrabold text-ink">مرورهای امروز کامل شده‌اند</h3>
                        <p class="text-xs mt-2 leading-7">کارت‌های بعدی در زمان مناسب دوباره به شما نمایش داده می‌شوند.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'quizzes'" x-cloak
 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
         class="mt-8">
        <div class="editorial-card">
            <h2 class="text-xl font-black text-ink pb-4 border-b border-hairline-soft">کارنامه و نتایج آزمون‌ها</h2>
            <div class="mt-6 divide-y divide-broca-sand">
                @forelse ($recentAttempts as $attempt)
                    <div class="py-4 flex flex-wrap items-center justify-between gap-4 text-xs">
                        <div>
                            <span class="text-muted block">{{ $attempt->quiz->course->title ?? '' }}</span>
                            <h3 class="text-sm font-extrabold text-ink mt-0.5">{{ $attempt->quiz->title ?? 'آزمون' }}</h3>
                            <span class="text-[11px] text-muted block mt-1">تاریخ: {{ $attempt->submitted_at?->timezone(config('broca.display_timezone'))->format('Y/m/d H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-left">
                                <span class="text-base font-black {{ $attempt->passed ? 'text-teal' : 'text-rausch' }}">{{ $attempt->score_percent }}٪</span>
                                <span class="text-[11px] block font-bold {{ $attempt->passed ? 'text-teal' : 'text-rausch' }}">{{ $attempt->passed ? 'قبول شده' : 'نیازمند تمرین' }} ({{ $attempt->correct_count }} از {{ $attempt->question_count }})</span>
                            </div>
                            <a href="{{ route('quizzes.attempts.show', [$attempt->quiz, $attempt]) }}" class="button-secondary">کارنامه تشریحی</a>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-xs text-muted">هنوز در آزمونی شرکت نکرده‌اید.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'discover'" x-cloak
 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
         class="mt-8 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-ink">دوره‌های پیشنهادی برای ادامه مسیر</h2>
                <p class="text-xs text-muted mt-1">با یک تصمیم ساده، مسیر یادگیری بعدی خود را شروع کنید.</p>
            </div>
            <a href="{{ route('catalog') }}" class="text-xs font-bold text-rausch underline">مشاهده کاتالوگ کامل ←</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse ($availableCourses as $c)
                <article class="course-card" data-reveal>
                    @if ($c->cover_image_path)
                        <img src="{{ $c->cover_image_path }}" alt="{{ $c->title }}" class="aspect-[16/10] w-full object-cover">
                    @endif
                    <div class="course-card__body">
                        <div class="course-card__meta">
                            <span class="badge-soft">{{ $c->subject->name ?? 'عمومی' }}</span>
                            <span class="badge-neutral">{{ $c->level ?: 'علوم پایه پزشکی' }}</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-ink">{{ $c->title }}</h3>
                            <p class="mt-3 text-sm text-muted leading-7">{{ $c->excerpt ?: $c->description }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-4 border-t border-hairline-soft">
                            <span class="text-xs text-muted font-bold">مدرس: {{ $c->author->name ?? '—' }}</span>
                            <form method="post" action="{{ route('courses.enroll', $c) }}">
                                @csrf
                                <button type="submit" class="button-primary">ثبت‌نام رایگان</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <p class="p-8 text-center text-xs text-muted col-span-2">تمامی دوره‌های موجود ثبت‌نام شده‌اند.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
