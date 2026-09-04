<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => StockMovementType::In,
            'qty' => fake()->numberBetween(1, 50),
            'note' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function in(int $qty = 10): static
    {
        return $this->state(['type' => StockMovementType::In, 'qty' => $qty]);
    }

    public function out(int $qty = 5): static
    {
        return $this->state(['type' => StockMovementType::Out, 'qty' => $qty]);
    }

    public function adjustment(int $qty = 100): static
    {
        return $this->state(['type' => StockMovementType::Adjustment, 'qty' => $qty]);
    }
}
