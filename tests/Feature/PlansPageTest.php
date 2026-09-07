<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Support\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pricing table on /plans: CodeFronts "Scale-Up Focused Plan Hover"
 * markup (.prc-05), styled with the Broca palette in resources/css/app.css.
 * The lineup is three cards — رایگان / یک‌ماهه ۲۷۰ تومان / سه‌ماهه ۶۰۰ تومان —
 * and the prices are DB content (price_irr in Rial), so the test seeds the
 * rows instead of relying on the seeder having been run. It seeds them from
 * PlanCatalog, the same source the seeder and `broca:sync-plans` read, so the
 * numbers cannot drift apart.
 */
class PlansPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedCanonicalLineup(): void
    {
        foreach (PlanCatalog::lineup() as $plan) {
            Plan::updateOrCreate(['code' => $plan['code']], $plan);
        }
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
        // Only the paid tier exists: the controller must still prepend free.
        $monthly = PlanCatalog::byCode(PlanCatalog::MONTHLY);
        $this->assertNotNull($monthly);
        Plan::updateOrCreate(['code' => $monthly['code']], $monthly);

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
