<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('lists products on the index page', function () {
    $products = Product::factory()->count(3)->create();

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords($products);
});

it('creates a product', function () {
    $category = Category::factory()->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'sku' => 'SKU-0001',
            'name' => 'Kabel HDMI',
            'category_id' => $category->id,
            'harga_jual' => 50000,
            'harga_modal' => 30000,
            'reorder_point' => 10,
            'unit' => 'pcs',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('sku', 'SKU-0001')->first();

    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('Kabel HDMI')
        ->and($product->category_id)->toBe($category->id)
        ->and((int) $product->current_stock)->toBe(0);
});

it('requires sku, name, and category', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'sku' => null,
            'name' => null,
            'category_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'sku' => 'required',
            'name' => 'required',
            'category_id' => 'required',
        ]);
});

it('enforces unique sku', function () {
    Product::factory()->create(['sku' => 'SKU-DUP']);
    $category = Category::factory()->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'sku' => 'SKU-DUP',
            'name' => 'Produk',
            'category_id' => $category->id,
            'harga_jual' => 1000,
            'harga_modal' => 500,
            'reorder_point' => 5,
            'unit' => 'pcs',
        ])
        ->call('create')
        ->assertHasFormErrors(['sku' => 'unique']);
});

it('edits a product', function () {
    $product = Product::factory()->create(['name' => 'Lama']);

    livewire(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['name' => 'Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh()->name)->toBe('Baru');
});

it('belongs to a category', function () {
    $product = Product::factory()->create();

    expect($product->category)->toBeInstanceOf(Category::class);
});

it('does not allow mass assigning current_stock', function () {
    $product = new Product;
    $product->fill(['current_stock' => 999, 'sku' => 'X', 'name' => 'Y', 'category_id' => 1]);

    expect($product->current_stock)->toBeNull();
});

it('filters products by stock status using the model stock status rule', function (string $status) {
    $products = collect([
        ['current_stock' => 0, 'reorder_point' => 10],
        ['current_stock' => 10, 'reorder_point' => 10],
        ['current_stock' => 11, 'reorder_point' => 10],
        ['current_stock' => 15, 'reorder_point' => 10],
        ['current_stock' => 16, 'reorder_point' => 10],
        ['current_stock' => 11, 'reorder_point' => 7],
        ['current_stock' => 12, 'reorder_point' => 7],
        ['current_stock' => 0, 'reorder_point' => 0],
        ['current_stock' => 1, 'reorder_point' => 0],
    ])->map(fn (array $attributes): Product => Product::factory()->create($attributes));

    [$matching, $notMatching] = $products->partition(
        fn (Product $product): bool => $product->stockStatus() === $status,
    );

    expect($matching)->not->toBeEmpty();

    livewire(ListProducts::class)
        ->filterTable('stock_status', $status)
        ->assertCanSeeTableRecords($matching)
        ->assertCanNotSeeTableRecords($notMatching);
})->with(['critical', 'low', 'safe']);

it('classifies stock status boundaries around the reorder point', function (int $currentStock, int $reorderPoint, string $expectedStatus) {
    $product = Product::factory()->create([
        'current_stock' => $currentStock,
        'reorder_point' => $reorderPoint,
    ]);

    $matchingStatuses = collect(['critical', 'low', 'safe'])
        ->filter(fn (string $status): bool => Product::withStockStatus($status)->whereKey($product->id)->exists())
        ->values()
        ->all();

    expect($product->stockStatus())->toBe($expectedStatus)
        ->and($matchingStatuses)->toBe([$expectedStatus]);
})->with([
    'zero reorder point with zero stock' => [0, 0, 'critical'],
    'zero reorder point with stock' => [1, 0, 'safe'],
    'at reorder point' => [10, 10, 'critical'],
    'just above reorder point' => [11, 10, 'low'],
    'at low threshold' => [15, 10, 'low'],
    'above low threshold' => [16, 10, 'safe'],
    'rounded half up threshold' => [11, 7, 'low'],
    'above rounded threshold' => [12, 7, 'safe'],
]);

it('shows all products when the stock status filter is cleared', function () {
    $products = Product::factory()->count(3)->sequence(
        ['current_stock' => 1, 'reorder_point' => 10],
        ['current_stock' => 12, 'reorder_point' => 10],
        ['current_stock' => 100, 'reorder_point' => 10],
    )->create();

    livewire(ListProducts::class)
        ->filterTable('stock_status', 'critical')
        ->resetTableFilters()
        ->assertCanSeeTableRecords($products);
});

it('ignores an unknown stock status filter value', function () {
    $products = Product::factory()->count(2)->sequence(
        ['current_stock' => 1, 'reorder_point' => 10],
        ['current_stock' => 100, 'reorder_point' => 10],
    )->create();

    livewire(ListProducts::class)
        ->filterTable('stock_status', 'bogus')
        ->assertCanSeeTableRecords($products);
});

it('lets staff filter products by stock status', function () {
    $this->actingAs(userWithRole('staff'));

    $critical = Product::factory()->create(['current_stock' => 1, 'reorder_point' => 10]);
    $safe = Product::factory()->create(['current_stock' => 100, 'reorder_point' => 10]);

    livewire(ListProducts::class)
        ->filterTable('stock_status', 'critical')
        ->assertCanSeeTableRecords([$critical])
        ->assertCanNotSeeTableRecords([$safe]);
});
