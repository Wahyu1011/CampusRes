<?php

namespace Tests\Feature;

use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_rejects_a_non_email_username(): void
    {
        $response = $this->post('/login', [
            'email' => 'campus-user',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[DataProvider('demoActorCredentials')]
    public function test_seeded_demo_actor_can_log_in(string $email, string $password, string $role): void
    {
        $this->seed(DemoUserSeeder::class);

        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => $role,
            'account_status' => 'approved',
        ]);
    }

    public static function demoActorCredentials(): array
    {
        return [
            'admin' => ['admin@campusres.test', 'admin123', 'admin'],
            'petugas' => ['petugas@campusres.test', 'petugas123', 'petugas'],
            'pengguna' => ['user@user.com', 'user123', 'user'],
        ];
    }
}
