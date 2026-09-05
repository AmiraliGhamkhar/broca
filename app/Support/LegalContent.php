<?php

namespace App\Support;

/**
 * Single source of truth for the legal documents' body copy.
 *
 * The HTML pages (resources/views/legal/placeholder.blade.php), the Markdown
 * twins (App\Services\SiteMarkdown) and any future export all render from
 * here, so the two formats can never drift apart.
 *
 * NOTE (pre-launch gate): the real legal copy is still owed by the client —
 * when it lands, replace the bodies in all() and every format updates.
 */
class LegalContent
{
    /**
     * @return array<string, array{heading:string, sections:array<int, array{h?:string, p:string}>}>
     *         page key (matches the /{page} route) → document
     */
    public static function all(): array
    {
        return [
            'terms' => [
                'heading' => 'شرایط استفاده',
                'sections' => [
                    ['h' => '۱. مالکیت فکری و حق نشر', 'p' => 'تمام ویدیوها، متون و جزوات اختصاصی محفوظ و متعلق به آکادمی بروکا است. هرگونه بازنشر، فروش یا انتشار غیرمجاز فایل‌ها پیگرد قانونی دارد.'],
                    ['h' => '۲. اشتراک‌های آموزشی', 'p' => 'پلن‌های اشتراک دارای مدت زمان مشخص (یک‌ماهه و سه‌ماهه) بوده و پس از اتمام دوره نیازمند تمدید توسط کاربر می‌باشند.'],
                ],
            ],
            'privacy' => [
                'heading' => 'حریم خصوصی',
                'sections' => [
                    ['h' => '۱. اطلاعات جمع‌آوری شده', 'p' => 'بروکا تنها اطلاعات ضروری شامل نام، آدرس ایمیل و شماره همراه را جهت احراز هویت، فعال‌سازی اشتراک و ذخیره روند مرور فلش‌کارت‌ها جمع‌آوری می‌کند.'],
                    ['h' => '۲. امنیت پرداخت‌ها', 'p' => 'اطلاعات حساس بانکی نظیر شماره کارت و رمز دوم در درگاه پرداخت شاپرک (زرین‌پال) پردازش شده و در سرورهای بروکا ذخیره نمی‌شوند.'],
                ],
            ],
            'medical-disclaimer' => [
                'heading' => 'بیانیهٔ پزشکی',
                'sections' => [
                    ['h' => 'سلب مسئولیت صریح بالینی (YMYL Medical Disclaimer)', 'p' => 'تمامی محتواهای چندرسانه‌ای، متون، آزمون‌ها و مقالات منتشرشده در وب‌سایت بروکا منحصراً با هدف ارتقای دانش تئوری و آمادگی دانشجویان رشته‌های پزشکی، دندانپزشکی، داروسازی و پیراپزشکی تولید شده‌اند.'],
                    ['h' => '۱. عدم ارائه مشاوره درمانی', 'p' => 'هیچ بخشی از محتوای این سامانه نباید به عنوان مشاوره پزشکی، تشخیص بیماری، پروتکل تجویز دارو یا جایگزین مراجعه به پزشک متخصص تلقی گردد.'],
                    ['h' => '۲. بازبینی علمی اعضای هیئت علمی', 'p' => 'دوره‌ها و مقالات با استناد به منابع معتبر جهانی (از جمله گایتون، هاریسون، گری و نتر) توسط اساتید تألیف و توسط متخصصین مستقل بازبینی می‌شوند، اما علم پزشکی پیوسته در حال تغییر است.'],
                ],
            ],
            'contact' => [
                'heading' => 'تماس با بروکا',
                'sections' => [
                    ['h' => 'راه‌های ارتباط و پشتیبانی', 'p' => 'ایمیل پشتیبانی: support@broca.test — ساعات پاسخگویی: شنبه تا چهارشنبه ۹ الی ۱۷.'],
                ],
            ],
        ];
    }

    public static function document(string $page): ?array
    {
        return self::all()[$page] ?? null;
    }

    /** @return array<string, string> page key → heading */
    public static function headings(): array
    {
        return array_map(fn (array $document): string => $document['heading'], self::all());
    }
}
