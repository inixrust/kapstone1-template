<?php

use App\Filament\Widgets\CriticalStockChart;
use App\Filament\Widgets\DeadStockChart;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryAnalyticsService;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->service = app(InventoryAnalyticsService::class);
});

it('identifies critical and low stock products only', function () {
    $critical = Product::factory()->create(['name' => 'Kritis', 'current_stock' => 2, 'reorder_point' => 10]);
    $low = Product::factory()->create(['name' => 'Menipis', 'current_stock' => 12, 'reorder_point' => 10]);
    Product::factory()->create(['name' => 'Aman', 'current_stock' => 100, 'reorder_point' => 10]);

    $result = $this->service->criticalStockProducts();

    expect($result)->toHaveCount(2)
        ->and($result->pluck('name')->all())->toBe(['Kritis', 'Menipis']) // urut paling genting dulu
        ->and($result->first()->is($critical))->toBeTrue()
        ->and($result->last()->is($low))->toBeTrue();
});

it('identifies dead stock products with no movement in the window', function () {
    $deadOld = Product::factory()->create(['name' => 'Mengendap']);
    StockMovement::factory()->for($deadOld)->in(10)->create(['created_at' => now()->subDays(70)]);

    $active = Product::factory()->create(['name' => 'Aktif']);
    StockMovement::factory()->for($active)->in(10)->create(['created_at' => now()->subDays(5)]);

    $neverMoved = Product::factory()->create(['name' => 'Belum Pernah']);

    $result = $this->service->deadStockProducts(60);

    $names = $result->pluck('name')->all();

    expect($names)->toContain('Mengendap')
        ->and($names)->toContain('Belum Pernah')
        ->and($names)->not->toContain('Aktif');
});

it('renders critical and dead stock charts', function () {
    $this->actingAs(adminUser());
    Product::factory()->create(['current_stock' => 1, 'reorder_point' => 10]);

    livewire(CriticalStockChart::class)->assertSuccessful();
    livewire(DeadStockChart::class)->assertSuccessful();
});
