<?php

use App\Filament\Widgets\MovementTrendChart;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryAnalyticsService;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->service = app(InventoryAnalyticsService::class);
});

it('aggregates in and out quantities per day for the last 30 days', function () {
    $product = Product::factory()->create();

    StockMovement::factory()->for($product)->in(10)->create(['created_at' => now()]);
    StockMovement::factory()->for($product)->out(4)->create(['created_at' => now()]);
    StockMovement::factory()->for($product)->in(5)->create(['created_at' => now()->subDays(2)]);

    // di luar jendela 30 hari → tidak dihitung
    StockMovement::factory()->for($product)->in(99)->create(['created_at' => now()->subDays(45)]);

    $trend = $this->service->movementTrend(30);

    expect($trend['labels'])->toHaveCount(30)
        ->and(array_sum($trend['in']))->toBe(15)
        ->and(array_sum($trend['out']))->toBe(4)
        ->and(end($trend['in']))->toBe(10)  // hari ini
        ->and(end($trend['out']))->toBe(4);
});

it('fills days without movement with zero', function () {
    $product = Product::factory()->create();
    StockMovement::factory()->for($product)->in(7)->create(['created_at' => now()]);

    $trend = $this->service->movementTrend(30);

    // 29 hari pertama tanpa movement = 0
    expect($trend['in'][0])->toBe(0)
        ->and($trend['out'][0])->toBe(0);
});

it('renders the trend chart widget', function () {
    $this->actingAs(adminUser());
    $product = Product::factory()->create();
    StockMovement::factory()->for($product)->in(3)->create();

    livewire(MovementTrendChart::class)->assertSuccessful();
});
