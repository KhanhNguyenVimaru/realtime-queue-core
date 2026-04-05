<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_user_management_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/admin/users');

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Forbidden. Admin access only.',
            ]);
    }

    public function test_admin_can_list_and_create_users(): void
    {
        $admin = User::factory()->admin()->create();

        Passport::actingAs($admin);

        User::factory()->count(2)->create();

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(3, 'users');

        $this->postJson('/api/admin/users', [
            'name' => 'New Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'manager@example.com')
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_admin_can_update_and_delete_users(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Passport::actingAs($admin);

        $this->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'password' => '',
            'role' => 'admin',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated User')
            ->assertJsonPath('user.role', 'admin');

        $this->deleteJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJson([
                'message' => 'User deleted successfully.',
            ]);
    }
}
