<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_user_management_screen(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data')
            ->has('availableRoles')
        );
    }

    public function test_super_admin_can_update_user_role(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();
        /** @var User $targetUser */
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->from('/admin/users')->actingAs($superAdmin)->put("/admin/users/{$targetUser->id}/role", [
            'role' => 'content_manager',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertEquals('content_manager', $targetUser->fresh()->role);
    }

    public function test_prevent_demoting_the_last_super_admin(): void
    {
        /** @var User $loneSuperAdmin */
        $loneSuperAdmin = User::factory()->superAdmin()->create();

        $response = $this->from('/admin/users')->actingAs($loneSuperAdmin)->put("/admin/users/{$loneSuperAdmin->id}/role", [
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('error');
        $this->assertEquals('super_admin', $loneSuperAdmin->fresh()->role);
    }

    public function test_prevent_self_deactivation_and_last_super_admin_deactivation(): void
    {
        /** @var User $loneSuperAdmin */
        $loneSuperAdmin = User::factory()->superAdmin()->create();

        // Attempting to deactivate oneself
        $response = $this->from('/admin/users')->actingAs($loneSuperAdmin)->post("/admin/users/{$loneSuperAdmin->id}/toggle-status");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('error');
        $this->assertTrue($loneSuperAdmin->fresh()->is_active);

        // Deactivating another regular user should succeed
        /** @var User $regularUser */
        $regularUser = User::factory()->create(['is_active' => true]);

        $deactivateResponse = $this->from('/admin/users')->actingAs($loneSuperAdmin)->post("/admin/users/{$regularUser->id}/toggle-status");

        $deactivateResponse->assertRedirect('/admin/users');
        $this->assertFalse($regularUser->fresh()->is_active);
    }

    public function test_deactivated_admin_is_rejected_by_admin_middleware(): void
    {
        /** @var User $deactivatedAdmin */
        $deactivatedAdmin = User::factory()->admin()->create(['is_active' => false]);

        $response = $this->actingAs($deactivatedAdmin)->get('/admin');

        $response->assertStatus(403);
    }
}
