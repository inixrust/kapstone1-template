<?php

use App\Filament\Widgets\AbcAnalysisChart;
use App\Filament\Widgets\TopMovingProductsChart;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryAnalyticsService;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->service = app(InventoryAnalyticsService::class);
});

it('ranks top moving products by total out quantity', function () {
    $p1 = Product::factory()->create(['name' => 'P1']);
    $p2 = Product::factory()->create(['name' => 'P2']);
    $p3 = Product::factory()->create(['name' => 'P3']);

    StockMovement::factory()->for($p1)->in(200)->create();      // in diabaikan
    StockMovement::factory()->for($p1)->out(30)->create();
    StockMovement::factory()->for($p2)->out(10)->create();
    StockMovement::factory()->for($p3)->out(50)->create();

    // di luar 30 hari → diabaikan
    StockMovement::factory()->for($p2)->out(999)->create(['created_at' => now()->subDays(45)]);

    $result = $this->service->topMovingProducts(30, 10);

    expect($result->keys()->all())->toBe(['P3', 'P1', 'P2'])
        ->and($result['P3'])->toBe(50)
        ->and($result['P1'])->toBe(30)
        ->and($result['P2'])->toBe(10);
});

it('classifies products with ABC analysis', function () {
    Product::factory()->create(['name' => 'A-item', 'current_stock' => 80, 'harga_modal' => 100]); // 8000
    Product::factory()->create(['name' => 'B-item', 'current_stock' => 15, 'harga_modal' => 100]); // 1500
    Product::factory()->create(['name' => 'C-item', 'current_stock' => 5, 'harga_modal' => 100]);  // 500

    $abc = $this->service->abcAnalysis();

    $byName = $abc->keyBy('name');

    expect($byName['A-item']['class'])->toBe('A')
        ->and($byName['A-item']['cumulative_percentage'])->toBe(80.0)
        ->and($byName['B-item']['class'])->toBe('B')
        ->and($byName['B-item']['cumulative_percentage'])->toBe(95.0)
        ->and($byName['C-item']['class'])->toBe('C')
        ->and($byName['C-item']['cumulative_percentage'])->toBe(100.0);
});

it('restricts advanced charts to manager and admin', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(userWithRole('staff'));
    expect(TopMovingProductsChart::canView())->toBeFalse()
        ->and(AbcAnalysisChart::canView())->toBeFalse();

    $this->actingAs(userWithRole('manager'));
    expect(TopMovingProductsChart::canView())->toBeTrue()
        ->and(AbcAnalysisChart::canView())->toBeTrue();
});

it('renders the advanced charts for admin', function () {
    $this->actingAs(adminUser());
    $product = Product::factory()->create(['current_stock' => 10, 'harga_modal' => 100]);
    StockMovement::factory()->for($product)->out(3)->create();

    livewire(TopMovingProductsChart::class)->assertSuccessful();
    livewire(AbcAnalysisChart::class)->assertSuccessful();
});
