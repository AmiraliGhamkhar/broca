<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin user-management invariants: an admin can never be removed from the
 * role when they are the last administrator, and self-lockout is blocked.
 */
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_demote_the_last_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->patch(route('admin.users.update', $admin), ['status' => 'active', 'is_admin' => false])
            ->assertSessionHasErrors('is_admin');

        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_admin_can_demote_another_admin_when_one_would_remain(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAsAdmin($actor)
            ->patch(route('admin.users.update', $target), ['status' => 'active', 'is_admin' => false])
            ->assertRedirect();

        $this->assertFalse($target->fresh()->is_admin);
        $this->assertTrue($actor->fresh()->is_admin);
    }

    public function test_admin_cannot_suspend_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->patch(route('admin.users.update', $admin), ['status' => 'suspended', 'is_admin' => true])
            ->assertSessionHasErrors('status');

        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_non_admin_cannot_update_users(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($actor)
            ->patch(route('admin.users.update', $target), ['status' => 'suspended', 'is_admin' => true])
            ->assertForbidden();
    }
}
