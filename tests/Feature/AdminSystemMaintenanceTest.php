<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminSystemMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_system_maintenance_dashboard(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get('/admin/system');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/System/Index')
            ->has('system')
            ->has('recentLogs')
        );
    }

    public function test_super_admin_can_clear_allowlisted_cache(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        Cache::put('test_key', 'test_val', 3600);

        $response = $this->from('/admin/system')->actingAs($superAdmin)->post('/admin/system/maintenance/clear-cache', [
            'cache_type' => 'app',
        ]);

        $response->assertRedirect('/admin/system');
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'system.cache_cleared',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_invalid_cache_target_is_rejected(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->from('/admin/system')->actingAs($superAdmin)->post('/admin/system/maintenance/clear-cache', [
            'cache_type' => 'arbitrary_invalid_target',
        ]);

        $response->assertSessionHasErrors('cache_type');
    }

    public function test_super_admin_can_trigger_catalog_rescan_and_analytics_aggregation(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        $scanResponse = $this->from('/admin/system')->actingAs($superAdmin)->post('/admin/system/maintenance/run-scanner');
        $scanResponse->assertRedirect('/admin/system');

        $aggregateResponse = $this->from('/admin/system')->actingAs($superAdmin)->post('/admin/system/maintenance/run-analytics-aggregation');
        $aggregateResponse->assertRedirect('/admin/system');
    }

    public function test_super_admin_can_view_audit_logs(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->superAdmin()->create();

        AdminAuditLog::create([
            'user_id' => $superAdmin->id,
            'user_email' => $superAdmin->email,
            'action' => 'system_checked',
            'resource_type' => 'system',
            'description' => 'System checked by super admin',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/system/logs');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/System/Logs')
            ->has('logs.data', 1)
        );
    }
}
