<?php

namespace App\Support;

/**
 * Single source of truth for the plans-page FAQ. Rendered in the Blade view,
 * its FAQPage JSON-LD block, and the /plans.md twin — one edit updates all.
 */
class PlanFaq
{
    /** @return array<int, array{q:string, a:string}> */
    public static function all(): array
    {
        return [
            ['q' => 'حساب رایگان چه امکانی می‌دهد؟', 'a' => 'حساب رایگان برای ارزیابی کیفیت یادگیری طراحی شده و امکان تجربه نمونه‌درس‌ها، بخشی از جزوات، فلش‌کارت‌ها و سؤال‌های منتخب را فراهم می‌کند.'],
            ['q' => 'بعد از پرداخت چه اتفاقی می‌افتد؟', 'a' => 'پس از تکمیل موفق پرداخت، فاکتور ثبت می‌شود و دسترسی اشتراک طبق پلن انتخابی فعال خواهد شد.'],
            ['q' => 'اگر اشتراک فعال داشته باشم می‌توانم دوباره خرید کنم؟', 'a' => 'برای جلوگیری از سردرگمی در وضعیت دسترسی، هنگام داشتن اشتراک فعال مسیر خرید جدید محدود می‌شود و وضعیت فعلی شما در داشبورد نمایش داده می‌شود.'],
            // (The 4th entry used to be meta copy about the page's own design
            //  — not a real customer question. Replaced 2026-09-05.)
            ['q' => 'مدت دسترسی من تا کِی معتبر است؟', 'a' => 'دسترسی تا پایان مدت پلن انتخابی معتبر است و پس از آن برای ادامه یادگیری نیاز به تمدید دارد؛ وضعیت دقیق انقضا همیشه در داشبورد نمایش داده می‌شود.'],
        ];
    }
}
