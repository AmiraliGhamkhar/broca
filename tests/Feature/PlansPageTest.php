<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pricing table on /plans: CodeFronts "Scale-Up Focused Plan Hover"
 * markup (.prc-05), styled with the Broca palette in resources/css/app.css.
 * The lineup is three cards — رایگان / یک‌ماهه ۲۷۰ تومان / سه‌ماهه ۶۰۰ تومان —
 * and the prices are DB content (price_irr in Rial), so the test seeds the
 * rows instead of relying on the seeder having been run.
 */
class PlansPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedCanonicalLineup(): void
    {
        Plan::updateOrCreate(['code' => 'free'], [
            'name' => 'پلن پایه رایگان',
            'description' => 'تا ۲ ویدیوی منتخب، ۱ جزوه، ۱۰ فلش‌کارت و ۱ سؤال آزمون در کل آرشیو — برای ارزیابی پیش از خرید.',
            'duration_months' => 0,
            'price_irr' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Plan::updateOrCreate(['code' => 'monthly'], [
            'name' => 'اشتراک یک‌ماهه طلایی',
            'description' => 'دسترسی نامحدود به تمام ویدیوهای بالینی، جزوات اختصاصی، آزمون‌ها و سیستم هوشمند SRS برای ۳۰ روز.',
            'duration_months' => 1,
            'price_irr' => 2700, // 270 Toman
            'sort_order' => 2,
            'is_active' => true,
        ]);
        Plan::updateOrCreate(['code' => 'quarterly'], [
            'name' => 'اشتراک سه‌ماهه جامع',
            'description' => 'دسترسی کامل به کل آرشیو دوره‌ها، آزمون‌های جامع و دسته‌های فلش‌کارت برای ۹۰ روز با تخفیف ویژه.',
            'duration_months' => 3,
            'price_irr' => 6000, // 600 Toman
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    public function test_plans_page_renders_the_three_pricing_cards(): void
    {
        $this->seedCanonicalLineup();

        $html = $this->get(route('plans'))->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, '<article class="prc-05__card'), 'expected exactly three pricing cards');
        $this->assertStringContainsString('۳ ماه دسترسی', $html);
        $this->assertStringContainsString('رایگان', $html);
    }

    public function test_paid_cards_show_toman_prices_and_free_card_says_free(): void
    {
        $this->seedCanonicalLineup();

        $html = $this->get(route('plans'))->assertOk()->getContent();

        // Rial is the stored unit; the card renders Toman (price_irr / 10).
        $this->assertStringContainsString('270', $html);
        $this->assertStringContainsString('600', $html);
        $this->assertStringContainsString('تومان', $html);
        $this->assertStringNotContainsString('2,700', $html, 'raw Rial must not leak into the card');
        $this->assertStringNotContainsString('6,000', $html, 'raw Rial must not leak into the card');

        // The free tier is labelled, not "0 تومان".
        $this->assertStringContainsString('prc-05__price is-free">رایگان<', $html);

        // Multi-month tier quotes an honest per-month equivalent (600 / 3 = 200).
        $this->assertStringContainsString('معادل', $html);
        $this->assertStringContainsString('>200<', $html);
    }

    public function test_three_month_tier_is_the_recommended_card(): void
    {
        $this->seedCanonicalLineup();

        $html = $this->get(route('plans'))->assertOk()->getContent();

        $this->assertStringContainsString('prc-05__card is-featured', $html);
        $this->assertStringContainsString('prc-05__ribbon', $html);
    }

    public function test_free_tier_is_surfaced_even_when_no_zero_price_row_exists(): void
    {
        Plan::updateOrCreate(['code' => 'monthly'], [
            'name' => 'اشتراک یک‌ماهه طلایی',
            'duration_months' => 1,
            'price_irr' => 2700,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $html = $this->get(route('plans'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, '<article class="prc-05__card'), 'the fallback free tier must be prepended by the controller');
        $this->assertStringContainsString('prc-05__price is-free">رایگان<', $html);
        $this->assertLessThan(
            strpos($html, '270'),
            strpos($html, 'prc-05__price is-free'),
            'the free card must lead the lineup'
        );
    }

    public function test_guests_get_a_register_cta_and_the_faq_stays_in_sync(): void
    {
        $this->seedCanonicalLineup();

        $response = $this->get(route('plans'))->assertOk();

        $response->assertSee(route('register'), false);
        // The Offer JSON-LD and the Markdown twin read the same rows as the cards:
        // Rial on the wire, Toman in /plans.md.
        $response->assertSee('priceCurrency', false);
        $this->assertStringContainsString('2700', $response->getContent());
        $this->get('/plans.md')->assertOk()->assertSee('270 تومان', false);
    }
}
