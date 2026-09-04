<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Data demo realistis agar seluruh dashboard punya isi bermakna:
     * ada produk fast-moving, stok kritis, dan dead stock.
     */
    public function run(): void
    {
        if (Product::query()->exists()) {
            return; // idempotent: jangan dobel saat re-seed
        }

        $categories = collect([
            'Elektronik', 'Perkakas', 'ATK', 'Jaringan', 'Aksesoris',
        ])->map(fn (string $name): Category => Category::factory()->create(['name' => $name]));

        $suppliers = Supplier::factory()->count(4)->create();

        $products = collect();
        foreach ($categories as $category) {
            $products = $products->merge(
                Product::factory()
                    ->count(5)
                    ->for($category)
                    ->recycle($suppliers)
                    ->create([
                        'reorder_point' => fn () => fake()->numberBetween(10, 25),
                    ])
            );
        }

        $products->values()->each(function (Product $product, int $index): void {
            // Stok awal masuk ~80 hari lalu (di luar jendela 60 hari)
            $this->move($product, StockMovementType::In, fake()->numberBetween(120, 400), Carbon::now()->subDays(80), 'Stok awal');

            // Setiap produk ke-7 dibiarkan sebagai dead stock (tanpa movement baru
            // dalam 60 hari terakhir)
            if ($index % 7 === 0) {
                return;
            }

            // Produk fast-moving (index kelipatan 3) mendapat lebih banyak keluar
            $movementCount = $index % 3 === 0 ? fake()->numberBetween(8, 15) : fake()->numberBetween(2, 6);

            for ($i = 0; $i < $movementCount; $i++) {
                $this->move(
                    $product,
                    fake()->boolean(70) ? StockMovementType::Out : StockMovementType::In,
                    fake()->numberBetween(3, 25),
                    Carbon::now()->subDays(fake()->numberBetween(0, 29)),
                );
            }

            // Beberapa produk dibuat kritis via out besar
            if ($index % 5 === 0) {
                $this->move($product, StockMovementType::Out, (int) max(1, $product->fresh()->current_stock - 5), Carbon::now()->subDays(1), 'Penjualan besar');
            }
        });
    }

    private function move(Product $product, StockMovementType $type, int $qty, Carbon $at, ?string $note = null): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'qty' => max(1, $qty),
            'note' => $note,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
