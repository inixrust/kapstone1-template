<?php

use App\Filament\Widgets\InventoryValueByCategoryChart;
use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryAnalyticsService;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->service = app(InventoryAnalyticsService::class);
});

it('aggregates stock by category', function () {
    $electronics = Category::factory()->create(['name' => 'Elektronik']);
    $tools = Category::factory()->create(['name' => 'Perkakas']);

    Product::factory()->for($electronics)->create(['current_stock' => 10]);
    Product::factory()->for($electronics)->create(['current_stock' => 5]);
    Product::factory()->for($tools)->create(['current_stock' => 20]);

    $result = $this->service->stockByCategory();

    expect($result['Elektronik'])->toBe(15)
        ->and($result['Perkakas'])->toBe(20);
});

it('aggregates inventory value by category using cost price', function () {
    $category = Category::factory()->create(['name' => 'Elektronik']);
    Product::factory()->for($category)->create(['current_stock' => 10, 'harga_modal' => 1000]);
    Product::factory()->for($category)->create(['current_stock' => 2, 'harga_modal' => 5000]);

    $result = $this->service->inventoryValueByCategory();

    // (10*1000) + (2*5000) = 20000
    expect($result['Elektronik'])->toBe(20000.0);
});

it('computes KPI values', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['current_stock' => 3, 'reorder_point' => 5, 'harga_modal' => 100]);
    Product::factory()->for($category)->create(['current_stock' => 50, 'reorder_point' => 5, 'harga_modal' => 200]);

    expect($this->service->totalProducts())->toBe(2)
        ->and($this->service->totalStockUnits())->toBe(53)
        ->and($this->service->criticalStockProductCount())->toBe(1) // hanya produk stok 3 (<=5)
        ->and($this->service->totalInventoryValue())->toBe(3 * 100.0 + 50 * 200.0);
});

it('hides the inventory value chart from staff', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(userWithRole('staff'));
    expect(InventoryValueByCategoryChart::canView())->toBeFalse();
});

it('shows the inventory value chart to manager and admin', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(userWithRole('manager'));
    expect(InventoryValueByCategoryChart::canView())->toBeTrue();

    $this->actingAs(userWithRole('admin'));
    expect(InventoryValueByCategoryChart::canView())->toBeTrue();
});

it('renders the dashboard for admin and staff without errors', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->count(3)->create();

    $this->actingAs(userWithRole('admin'))
        ->get('/admin')
        ->assertOk();

    $this->actingAs(userWithRole('staff'))
        ->get('/admin')
        ->assertOk();
});
