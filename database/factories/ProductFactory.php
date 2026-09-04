<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hargaModal = fake()->numberBetween(5_000, 500_000);

        return [
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'harga_modal' => $hargaModal,
            'harga_jual' => (int) round($hargaModal * fake()->randomFloat(2, 1.1, 1.6)),
            'reorder_point' => fake()->numberBetween(5, 30),
            'unit' => fake()->randomElement(['pcs', 'box', 'unit', 'pack']),
            'current_stock' => fake()->numberBetween(0, 200),
        ];
    }
}
