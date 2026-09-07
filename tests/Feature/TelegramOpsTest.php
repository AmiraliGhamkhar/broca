<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The bot's operations surface: health, plan-lineup repair, queue status and
 * account repair.
 *
 * These are the buttons an operator reaches for from a phone when the site is
 * misbehaving, which is precisely when there is no SSH session open — so they
 * are covered like any other admin path.
 */
class TelegramOpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.enabled', true);
        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.webhook_secret', 'secret-123');
        config()->set('services.telegram.admin_ids', ['42']);

        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);
    }

    public function test_health_report_is_sent_to_an_authorized_admin(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/health'), $this->headers())
            ->assertOk();

        // Assert on the decoded payload, not the wire bytes: the HTTP client
        // is free to escape non-ASCII however it likes.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains((string) ($request->data()['text'] ?? ''), 'وضعیت عملیاتی بروکا'));
    }

    public function test_the_plan_lineup_can_be_restored_from_the_bot(): void
    {
        $this->assertSame(0, Plan::query()->count());

        // Confirmation first — restoring touches product data.
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('plansrestore:1'), $this->headers())
            ->assertOk();

        $this->assertSame(0, Plan::query()->count(), 'the confirm step must not write anything');

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmplansrestore:1'), $this->headers())
            ->assertOk();

        $this->assertSame(3, Plan::query()->count());
        $this->assertDatabaseHas('plans', ['code' => 'monthly', 'price_irr' => 2700]);
        $this->assertDatabaseHas('plans', ['code' => 'quarterly', 'price_irr' => 6000]);
    }

    public function test_restoring_the_lineup_does_not_clobber_an_edited_price(): void
    {
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmplansrestore:1'), $this->headers())
            ->assertOk();

        Plan::query()->where('code', 'monthly')->firstOrFail()->forceFill(['price_irr' => 123000])->save();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmplansrestore:1'), $this->headers())
            ->assertOk();

        $this->assertSame(123000, (int) Plan::query()->where('code', 'monthly')->value('price_irr'));
    }

    public function test_the_bot_refuses_to_suspend_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        $this->postJson(
            route('telegram.webhook'),
            $this->callbackUpdate('confirmuser:'.$admin->id.':status:suspended'),
            $this->headers()
        )->assertOk();

        // The web panel has always refused this; the bot did not, and one tap
        // was enough to lock every administrator out of /admin.
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);

        Http::assertSent(fn ($request) => str_contains((string) ($request->data()['text'] ?? ''), 'آخرین مدیر فعال'));
    }

    public function test_an_admin_with_other_admins_around_can_still_be_suspended(): void
    {
        $first = User::factory()->admin()->create(['status' => 'active']);
        $second = User::factory()->admin()->create(['status' => 'active']);

        $this->postJson(
            route('telegram.webhook'),
            $this->callbackUpdate('confirmuser:'.$first->id.':status:suspended'),
            $this->headers()
        )->assertOk();

        $this->assertDatabaseHas('users', ['id' => $first->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'status' => 'active']);
    }

    public function test_support_can_verify_an_account_email_from_the_bot(): void
    {
        $user = User::factory()->unverified()->create();

        // `confirmuser:` applies; `useraction:` is the confirmation prompt.
        $this->postJson(
            route('telegram.webhook'),
            $this->callbackUpdate('confirmuser:'.$user->id.':email_verified:1'),
            $this->headers()
        )->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_support_can_verify_a_mobile_from_the_bot(): void
    {
        $user = User::factory()->unverified()->create(['phone' => '09123456789']);

        $this->postJson(
            route('telegram.webhook'),
            $this->callbackUpdate('confirmuser:'.$user->id.':phone_verified:1'),
            $this->headers()
        )->assertOk();

        $this->assertTrue($user->fresh()->hasVerifiedPhone());
    }

    public function test_queue_status_is_reachable_from_the_bot(): void
    {
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('ops:jobs'), $this->headers())
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains((string) ($request->data()['text'] ?? ''), 'وضعیت صف'));
    }

    public function test_ops_menu_is_offered_in_the_main_menu(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/start'), $this->headers())
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains(
            (string) ($request->data()['reply_markup'] ?? ''),
            'عملیات و سلامت'
        ));
    }

    public function test_an_unauthorized_sender_gets_no_ops_data(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/health', fromId: 999), $this->headers())
            ->assertOk();

        // The refusal is sent, the report is not: an unknown sender must learn
        // nothing about the install.
        Http::assertSent(fn ($request) => str_contains((string) ($request->data()['text'] ?? ''), 'فقط برای ادمین'));
        Http::assertNotSent(fn ($request) => str_contains((string) ($request->data()['text'] ?? ''), 'وضعیت عملیاتی'));
    }

    private function message(string $text, int $fromId = 42): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'date' => now()->timestamp,
                'chat' => ['id' => 1001, 'type' => 'private'],
                'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'Admin'],
                'text' => $text,
            ],
        ];
    }

    private function callbackUpdate(string $data, int $fromId = 42): array
    {
        return [
            'update_id' => 2,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => ['id' => $fromId, 'is_bot' => false, 'first_name' => 'Admin'],
                'data' => $data,
                'message' => [
                    'message_id' => 9,
                    'chat' => ['id' => 1001, 'type' => 'private'],
                    'date' => now()->timestamp,
                ],
            ],
        ];
    }

    private function headers(): array
    {
        return ['X-Telegram-Bot-Api-Secret-Token' => 'secret-123'];
    }
}
