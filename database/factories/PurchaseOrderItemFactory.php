<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Produk harus milik supplier PO; isi `product_id` secara eksplisit
 * (lihat helper `purchaseOrderWithItem()` di tests/Pest.php).
 *
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'qty' => fake()->numberBetween(1, 50),
            'unit_price' => fn (array $attributes): string => Product::query()->find($attributes['product_id'])?->harga_modal ?? '0',
        ];
    }
}
