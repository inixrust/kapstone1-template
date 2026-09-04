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
