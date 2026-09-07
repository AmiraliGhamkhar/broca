<?php

namespace App\Support;

class StructuredMessageParser
{
    /** Persian aliases keep Telegram forms fully usable in RTL. */
    private const KEY_ALIASES = [
        'شناسه' => 'id', 'نام' => 'name', 'عنوان' => 'title', 'کد' => 'code', 'توضیح' => 'description', 'توضیحات' => 'description',
        'خلاصه' => 'excerpt', 'محتوا' => 'content', 'دسته‌بندی' => 'category', 'نام_نویسنده' => 'author_name',
        'نام_بازبین' => 'reviewer_name', 'شناسه_نویسنده' => 'author_id', 'شناسه_بازبین' => 'reviewer_id',
        'شناسه_درس‌نامه' => 'subject_id', 'شناسه_دوره' => 'course_id', 'شناسه_دک' => 'flashcard_deck_id',
        'شناسه_آزمون' => 'quiz_id', 'ترتیب' => 'sort_order', 'وضعیت' => 'status', 'تاریخ_انتشار' => 'published_at',
        'نمایان' => 'is_visible', 'فعال' => 'is_active', 'رایگان' => 'is_free_designated', 'سطح' => 'level',
        'قیمت_ریال' => 'price_irr', 'مدت_ماه' => 'duration_months', 'مدت_ثانیه' => 'duration_seconds',
        'نوع_منبع' => 'source_mode', 'نشانی_پخش' => 'playback_url', 'نشانی_فایل' => 'file_url', 'نشانی_منبع' => 'source_url', 'شناسه_منبع' => 'source_asset_id',
        'آستانه_تکمیل' => 'completion_threshold_percent', 'متن_رو' => 'front', 'متن_پشت' => 'back',
        'راهنما' => 'hint', 'حدنصاب' => 'pass_threshold_percent', 'صورت_سؤال' => 'prompt', 'پاسخ_تشریحی' => 'explanation',
        'منبع' => 'source_citation', 'گزینه‌ها' => 'options', 'گزینه_درست' => 'correct_index',
        'عنوان_سئو' => 'meta_title', 'توضیح_سئو' => 'meta_description', 'تصویر_شاخص' => 'cover_image_path',
    ];

    /** @return array<string, string> */
    public static function parse(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $data = [];
        $blockKey = null;
        $blockLines = [];
        $keyPattern = '[\p{L}\p{M}0-9_\x{200C}-]+';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($blockKey !== null) {
                if (preg_match('/^\[\/('.$keyPattern.')\]$/iu', $trimmed, $closing) && self::key($closing[1]) === $blockKey) {
                    $data[$blockKey] = trim(implode("\n", $blockLines));
                    $blockKey = null;
                    $blockLines = [];
                    continue;
                }
                $blockLines[] = $line;
                continue;
            }

            if ($trimmed === '') {
                continue;
            }
            if (preg_match('/^\[('.$keyPattern.')\]$/iu', $trimmed, $matches)) {
                $blockKey = self::key($matches[1]);
                continue;
            }
            if (preg_match('/^('.$keyPattern.')\s*[:=：]\s*(.*)$/iu', $line, $matches)) {
                $key = self::key($matches[1]);
                $data[$key] = self::value($key, trim($matches[2]));
            }
        }

        if ($blockKey !== null) {
            $data[$blockKey] = trim(implode("\n", $blockLines));
        }

        return $data;
    }

    private static function key(string $key): string
    {
        $key = mb_strtolower(trim($key));

        return self::KEY_ALIASES[$key] ?? $key;
    }

    private static function value(string $key, string $value): string
    {
        if ($key === 'status') {
            return ['پیش‌نویس' => 'draft', 'در_بازبینی' => 'in_review', 'در بازبینی' => 'in_review', 'منتشرشده' => 'published', 'منتشر شده' => 'published', 'آرشیو' => 'archived'][$value] ?? $value;
        }
        if ($key === 'source_mode') {
            return ['بارگذاری' => 'upload', 'نشانی' => 'url', 'شناسه' => 'asset', 'نگه‌داری' => 'keep'][$value] ?? $value;
        }

        return $value;
    }
}
