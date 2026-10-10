<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_a_petugas_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Petugas Kampus',
            'email' => 'petugas@example.test',
            'password' => 'password123',
            'role' => 'petugas',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'petugas@example.test',
            'role' => 'petugas',
            'account_status' => 'approved',
        ]);
    }

    public function test_petugas_cannot_open_account_management(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $response = $this->actingAs($petugas)->get(route('users.index'));

        $response->assertForbidden();
    }
}
