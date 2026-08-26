@extends('layouts.app')

@section('title', 'پنل ادمین — ' . __('app.name'))

@section('content')
    <section class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold">پنل ادمین</h1>
        <p class="mt-2 text-broca-slate">مدیریت محتوای بروکا</p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">کاربران</h2>
                <p class="mt-2 text-3xl font-bold">1,234</p>
                <p class="mt-1 text-broca-slate">کاربر فعال</p>
            </div>
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">دوره‌ها</h2>
                <p class="mt-2 text-3xl font-bold">28</p>
                <p class="mt-1 text-broca-slate">دوره منتشر شده</p>
            </div>
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">اشتراک‌ها</h2>
                <p class="mt-2 text-3xl font-bold">456</p>
                <p class="mt-1 text-broca-slate">اشتراک فعال</p>
            </div>
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">بلاگ</h2>
                <p class="mt-2 text-3xl font-bold">12</p>
                <p class="mt-1 text-broca-slate">مقاله منتشر شده</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">فعالیت‌های اخیر</h2>
                <ul class="mt-4 space-y-3">
                    <li class="flex justify-between">
                        <span>دکتر علیرضا محمدی</span>
                        <span class="text-broca-slate">۱۴۰۲/۰۵/۱۵</span>
                    </li>
                    <li class="flex justify-between">
                        <span>دکتر فاطمه رضایی</span>
                        <span class="text-broca-slate">۱۴۰۲/۰۵/۱۴</span>
                    </li>
                    <li class="flex justify-between">
                        <span>دکتر حسین کریمی</span>
                        <span class="text-broca-slate">۱۴۰۲/۰۵/۱۳</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white border border-broca-sand rounded-lg p-4 lg:col-span-2">
                <h2 class="text-lg font-medium">گزارش‌های مالی</h2>
                <div class="mt-4 h-48 bg-broca-sand rounded flex items-center justify-center">
                    <p class="text-broca-slate">نمودار مالی (نمونه)</p>
                </div>
            </div>
        </div>
    </section>
@endsection
