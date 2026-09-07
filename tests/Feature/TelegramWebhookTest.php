<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.enabled', true);
        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.webhook_secret', 'secret-123');
        config()->set('services.telegram.admin_ids', ['42']);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['file_path' => 'documents/test.pdf']], 200),
        ]);
    }

    public function test_authorized_admin_can_create_a_plan_via_two_messages(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/plan_new'), $this->headers())
            ->assertOk();

        $this->assertDatabaseHas('telegram_chat_sessions', [
            'telegram_chat_id' => 1001,
            'workflow' => 'plan.form',
        ]);

        $this->postJson(route('telegram.webhook'), $this->message(implode("\n", [
            'code: vip-6m',
            'name: پلن ویژه شش‌ماهه',
            'description: دسترسی کامل',
            'price_irr: 25000000',
            'duration_months: 6',
            'is_active: yes',
            'sort_order: 9',
        ])), $this->headers())->assertOk();

        $this->assertDatabaseHas('plans', [
            'code' => 'vip-6m',
            'name' => 'پلن ویژه شش‌ماهه',
            'price_irr' => 25000000,
            'duration_months' => 6,
        ]);
        $this->assertDatabaseMissing('telegram_chat_sessions', [
            'telegram_chat_id' => 1001,
            'workflow' => 'plan.form',
        ]);
    }

    public function test_authorized_admin_can_create_a_blog_post_via_bot(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/blog_new'), $this->headers())
            ->assertOk();

        $this->postJson(route('telegram.webhook'), $this->message(implode("\n", [
            'title: مقاله تستی',
            'category: نورولوژی',
            'author_name: دکتر تست',
            'reviewer_name: دکتر بازبین',
            'excerpt: خلاصه کوتاه',
            'status: in_review',
            'meta_title: مقاله تستی',
            'meta_description: توضیح متا',
            '[content]',
            'این یک مقاله آزمایشی است.',
            '[/content]',
        ])), $this->headers())->assertOk();

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'مقاله تستی',
            'category' => 'نورولوژی',
            'status' => 'in_review',
        ]);
    }

    public function test_authorized_admin_can_open_a_flow_from_inline_callback(): void
    {
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('new:blog'), $this->headers())
            ->assertOk();

        $this->assertDatabaseHas('telegram_chat_sessions', [
            'telegram_chat_id' => 1001,
            'workflow' => 'blog.form',
        ]);
    }

    public function test_bot_rejects_direct_publish_from_form_submission(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/blog_new'), $this->headers())
            ->assertOk();

        $this->postJson(route('telegram.webhook'), $this->message(implode("\n", [
            'title: انتشار مستقیم',
            'author_name: دکتر تست',
            'reviewer_name: دکتر بازبین',
            'status: published',
            '[content]',
            'متن تست',
            '[/content]',
        ])), $this->headers())->assertOk();

        $this->assertDatabaseMissing('blog_posts', [
            'title' => 'انتشار مستقیم',
        ]);
    }

    public function test_unauthorized_sender_cannot_trigger_workflows(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/plan_new', fromId: 999), $this->headers())
            ->assertOk();

        $this->assertDatabaseMissing('telegram_chat_sessions', [
            'telegram_chat_id' => 1001,
            'workflow' => 'plan.form',
        ]);
    }

    public function test_missing_secret_header_is_rejected(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/help'))
            ->assertForbidden();
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

    public function test_empty_webhook_secret_fails_closed(): void
    {
        // Fail-closed (audit 2026-09-07): an enabled bot without a configured
        // secret must 403 every update — even one carrying any header value,
        // because there is no secret to match against.
        config()->set('services.telegram.webhook_secret', '');

        $this->postJson(route('telegram.webhook'), $this->message('/help'))
            ->assertForbidden();

        $this->postJson(route('telegram.webhook'), $this->message('/help'), $this->headers())
            ->assertForbidden();

        $this->assertDatabaseMissing('telegram_chat_sessions', [
            'telegram_chat_id' => 1001,
        ]);
    }

    public function test_wrong_secret_value_is_rejected(): void
    {
        $this->postJson(route('telegram.webhook'), $this->message('/help'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-token',
        ])->assertForbidden();
    }

    public function test_disabled_bot_returns_404(): void
    {
        config()->set('services.telegram.enabled', false);

        $this->postJson(route('telegram.webhook'), $this->message('/help'), $this->headers())
            ->assertNotFound();
    }

    public function test_users_list_and_manage_card_callbacks_render(): void
    {
        $users = User::factory()->count(2)->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('list:users:1'), $this->headers())
            ->assertOk();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('manage:user:'.$users->first()->id), $this->headers())
            ->assertOk();
    }

    public function test_stats_callback_renders(): void
    {
        User::factory()->count(3)->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('stats:run'), $this->headers())
            ->assertOk();
    }

    public function test_bot_refuses_to_suspend_the_last_active_admin(): void
    {
        // Web-panel parity (audit 2026-09-07): the panel refuses self-suspension;
        // the bot cannot identify "self" (telegram_admins has no site user_id),
        // so it enforces the stronger invariant — the last ACTIVE admin account
        // can never be suspended, or the panel locks out for everyone.
        $admin = User::factory()->admin()->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmuserstatus:'.$admin->id), $this->headers())
            ->assertOk();

        $this->assertSame('active', $admin->fresh()->status);

        // A regular user is suspendable without ceremony.
        $member = User::factory()->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmuserstatus:'.$member->id), $this->headers())
            ->assertOk();

        $this->assertSame('suspended', $member->fresh()->status);

        // And with a second active admin, the first one is suspendable too.
        User::factory()->admin()->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmuserstatus:'.$admin->id), $this->headers())
            ->assertOk();

        $this->assertSame('suspended', $admin->fresh()->status);
    }

    public function test_bot_refuses_demoting_the_only_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmuseradmin:'.$admin->id), $this->headers())
            ->assertOk();

        $this->assertTrue($admin->fresh()->is_admin);

        // A second ACTIVE admin can be demoted — the invariant is about
        // leaving at least one active admin, not about freezing roles.
        User::factory()->admin()->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmuseradmin:'.$admin->id), $this->headers())
            ->assertOk();

        $this->assertFalse($admin->fresh()->is_admin);
    }

    public function test_bot_can_enroll_and_unenroll_a_user_in_a_course(): void
    {
        $member = User::factory()->create();
        $course = Course::factory()->published()->create();

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmenroll:'.$member->id.':'.$course->id), $this->headers())
            ->assertOk();

        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $member->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('confirmunenroll:'.$member->id.':'.$course->id), $this->headers())
            ->assertOk();

        // Cancelled — not deleted — so enrollment history stays auditable.
        $this->assertDatabaseHas('course_enrollments', [
            'user_id' => $member->id,
            'course_id' => $course->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_stale_manage_button_answers_instead_of_500ing(): void
    {
        // A manage callback for an id that no longer exists must not blow up
        // the webhook (Telegram would retry the update forever) — it answers
        // with a Persian "not found" message and a 200.
        $this->postJson(route('telegram.webhook'), $this->callbackUpdate('manage:course:987654'), $this->headers())
            ->assertOk();
    }

    public function test_backup_command_queues_the_job_instead_of_running_it_inline(): void
    {
        // Round-6 audit I-4: the dump + gzip + upload can outlive Telegram's
        // webhook patience (~60s), triggering a retry and a duplicate dump.
        // The work must move to the database queue (drained by the cron
        // worker), never run inside the webhook request.
        Bus::fake();

        $this->postJson(route('telegram.webhook'), $this->message('/backup_db'), $this->headers())
            ->assertOk();

        Bus::assertDispatched(\App\Jobs\RunDatabaseBackup::class, fn (\App\Jobs\RunDatabaseBackup $job) => $job->chatId === 1001);
    }

    private function headers(): array
    {
        return ['X-Telegram-Bot-Api-Secret-Token' => 'secret-123'];
    }
}
