<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->postJson(route('telegram.webhook'), $this->callback('new:blog'), $this->headers())
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

    private function callback(string $data, int $fromId = 42): array
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
