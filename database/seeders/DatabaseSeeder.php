<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Catatan: TIDAK memakai WithoutModelEvents karena DemoDataSeeder bergantung
     * pada StockMovementObserver (recalc current_stock) dan activity log yang
     * berjalan lewat model events.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ShieldSeeder::class,
            RoleSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
