<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun demo untuk pelatihan. Role diberikan di RoleSeeder (m2.2).
     *
     * @var list<array{name: string, email: string}>
     */
    public const DEMO_USERS = [
        ['name' => 'Staff Gudang', 'email' => 'staff@demo.com'],
        ['name' => 'Manager Inventory', 'email' => 'manager@demo.com'],
        ['name' => 'Administrator', 'email' => 'admin@demo.com'],
    ];

    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        foreach (self::DEMO_USERS as $demoUser) {
            User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
