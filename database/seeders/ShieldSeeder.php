<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    /**
     * Buat permission Shield (Action:Resource) untuk semua entity panel admin.
     * Memakai `shield:generate --option=permissions` supaya penamaan selalu
     * konsisten dengan konvensi Shield dan otomatis mengikuti resource baru
     * (mis. StockMovement di m3.1) tanpa daftar manual. Mode `permissions`
     * tidak menulis ulang file policy.
     */
    public function run(): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
