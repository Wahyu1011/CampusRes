<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacyAdmin = User::query()->where('email', 'admin')->where('role', 'admin')->first();

        if ($legacyAdmin !== null && ! User::query()->where('email', 'admin@campusres.test')->exists()) {
            $legacyAdmin->update([
                'name' => 'Admin Demo',
                'email' => 'admin@campusres.test',
                'password' => Hash::make('admin123'),
                'account_status' => 'approved',
            ]);
        }

        User::updateOrCreate(
            ['email' => 'admin@campusres.test'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'account_status' => 'approved',
            ],
        );

        User::updateOrCreate(
            ['email' => 'petugas@campusres.test'],
            [
                'name' => 'Petugas Demo',
                'password' => Hash::make('petugas123'),
                'role' => 'petugas',
                'account_status' => 'approved',
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@user.com'],
            [
                'name' => 'Pengguna Demo',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'account_status' => 'approved',
            ],
        );
    }
}
