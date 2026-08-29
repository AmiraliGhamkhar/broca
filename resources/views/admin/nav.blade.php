@php
    $currentRoute = Route::currentRouteName();
@endphp
<div class="mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-hairline-soft">
        <div class="flex items-center gap-3">
            <span class="grid size-11 place-items-center rounded-2xl bg-ink text-white font-bold text-lg shadow-sm">ب</span>
            <div>
                <h1 class="font-display text-2xl  text-ink">استودیو مدیریت بروکا</h1>
                <p class="text-xs font-bold text-muted">کنترل کامل محتوا، دوره‌ها، رسانه‌ها و کاربران پزشکی</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-full border border-ink/20 text-xs font-bold hover:bg-surface-soft">داشبورد دانشجو ←</a>
            <a href="{{ url('/') }}" target="_blank" class="px-4 py-2 rounded-full bg-ink text-white text-xs font-bold hover:bg-rausch">مشاهده سایت</a>
        </div>
    </div>

    <nav class="mt-4 flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-none" aria-label="منوی مدیریت">
        <a href="{{ route('admin.dashboard') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ $currentRoute === 'admin.dashboard' ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            📊 پیشخوان
        </a>
        <a href="{{ route('admin.courses.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.courses') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            🎓 دوره‌ها
        </a>
        <a href="{{ route('admin.subjects.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.subjects') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            📚 درس‌نامه‌ها
        </a>
        <a href="{{ route('admin.videos.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.videos') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            🎥 ویدیوها
        </a>
        <a href="{{ route('admin.notes.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.notes') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            📄 جزوات
        </a>
        <a href="{{ route('admin.flashcards.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.flashcards') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            🗂 فلش‌کارت‌ها
        </a>
        <a href="{{ route('admin.quizzes.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.quizzes') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            📝 آزمون‌ها
        </a>
        <a href="{{ route('admin.users.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.users') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            👥 کاربران
        </a>
        <a href="{{ route('admin.plans.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.plans') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            💳 پلن‌ها
        </a>
        <a href="{{ route('admin.activity.index') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.activity') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            🛡 لاگ فعالیت
        </a>
        <a href="{{ route('admin.two-factor.edit') }}"
           class="px-4 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ str_starts_with($currentRoute, 'admin.two-factor') ? 'bg-ink text-white shadow-sm' : 'bg-white text-muted hover:bg-surface-soft border border-hairline-soft' }}">
            🔐 امنیت 2FA
        </a>
    </nav>
</div>
