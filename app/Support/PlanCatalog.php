<?php

namespace App\Support;

/**
 * The canonical subscription lineup: free / 1-month / 3-month.
 *
 * ONE SOURCE OF TRUTH, AND IT LIVES IN CODE — HERE IS WHY.
 *
 * Plans used to exist only as rows written by `db:seed`. The cPanel deploy
 * hook runs `artisan migrate` but never `artisan db:seed`, so on a host where
 * the seeder was never run by hand the `plans` table stays empty: /plans then
 * renders the controller's built-in free fallback and NOTHING else, i.e. one
 * card where the client expects three ("what happened to the two other plan
 * cards"). The prices are a confirmed product decision (270 T / 600 T), so
 * they are declared here and pushed into the database by an idempotent
 * command (`php artisan broca:sync-plans`, wired into the deploy hook):
 *
 *   - the seeder seeds from this array (no second copy of the numbers);
 *   - the sync command creates what is missing and repairs what drifted;
 *   - the admin panel and the Telegram bot both read it to warn when the
 *     lineup is incomplete.
 *
 * Prices stay editable in the database afterwards — sync only re-asserts the
 * canonical values when `--reset` is passed.
 */
final class PlanCatalog
{
    public const FREE = 'free';

    public const MONTHLY = 'monthly';

    public const QUARTERLY = 'quarterly';

    /**
     * @return list<array{code: string, name: string, description: string, duration_months: int, price_irr: int, sort_order: int, is_active: bool}>
     */
    public static function lineup(): array
    {
        return [
            [
                'code' => self::FREE,
                'name' => 'پلن پایه رایگان',
                // Global cap wording — mirrors EntitlementService constants;
                // the quota is shared across the whole archive, not per course.
                'description' => 'تا ۲ ویدیوی منتخب، ۱ جزوه، ۱۰ فلش‌کارت و ۱ سؤال آزمون در کل آرشیو — برای ارزیابی پیش از خرید.',
                'duration_months' => 0,
                'price_irr' => 0,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => self::MONTHLY,
                'name' => 'اشتراک یک‌ماهه طلایی',
                'description' => 'دسترسی نامحدود به تمام ویدیوهای بالینی، جزوات اختصاصی، آزمون‌ها و سیستم هوشمند SRS برای ۳۰ روز.',
                'duration_months' => 1,
                'price_irr' => 2700, // 270 Toman — stored in Rial
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => self::QUARTERLY,
                'name' => 'اشتراک سه‌ماهه جامع',
                'description' => 'دسترسی کامل به کل آرشیو دوره‌ها، آزمون‌های جامع و دسته‌های فلش‌کارت برای ۹۰ روز با تخفیف ویژه.',
                'duration_months' => 3,
                'price_irr' => 6000, // 600 Toman
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return array{code: string, name: string, description: string, duration_months: int, price_irr: int, sort_order: int, is_active: bool}|null
     */
    public static function byCode(string $code): ?array
    {
        foreach (self::lineup() as $plan) {
            if ($plan['code'] === $code) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * The free tier, used as a fallback card when the table has no zero-price
     * row (a fresh install must never show an empty pricing page).
     *
     * @return array{code: string, name: string, description: string, duration_months: int, price_irr: int, sort_order: int, is_active: bool}
     */
    public static function free(): array
    {
        return self::byCode(self::FREE) ?? self::lineup()[0];
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_column(self::lineup(), 'code');
    }
}
