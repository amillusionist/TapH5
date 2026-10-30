<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_regular_authenticated_user_receives_403_when_accessing_admin(): void
    {
        /** @var User $regularUser */
        $regularUser = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($regularUser)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_administrator_can_access_admin_dashboard(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_call_admin_mutation_endpoints(): void
    {
        /** @var User $regularUser */
        $regularUser = User::factory()->create([
            'role' => 'user',
        ]);

        // Attempting to create category
        $response = $this->actingAs($regularUser)->post('/admin/categories', [
            'name' => 'Hacked Category',
            'slug' => 'hacked-category',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('categories', ['slug' => 'hacked-category']);
    }

    public function test_public_registration_never_grants_admin_role_even_if_role_passed(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin', // Malicious attempt to inject role
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'attacker@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_make_admin_artisan_command_can_promote_existing_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'role' => 'user',
        ]);

        $this->artisan('make:admin existing@example.com')
            ->expectsOutputToContain('promoted to administrator')
            ->assertExitCode(0);

        $this->assertEquals('admin', $user->fresh()->role);
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_make_admin_artisan_command_can_create_new_administrator(): void
    {
        $this->artisan('make:admin newadmin@example.com --name="Super Admin" --password="Password123!"')
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $created = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($created);
        $this->assertEquals('admin', $created->role);
        $this->assertTrue($created->isAdmin());
    }

    public function test_dashboard_route_redirects_admin_to_admin_panel(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }
}

