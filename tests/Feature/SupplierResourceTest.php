<?php

use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\RelationManagers\ProductsRelationManager;
use App\Models\Product;
use App\Models\Supplier;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('lists suppliers on the index page', function () {
    $suppliers = Supplier::factory()->count(3)->create();

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords($suppliers);
});

it('creates a supplier', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'PT Sumber Makmur',
            'email' => 'sales@sumbermakmur.test',
            'phone' => '021-555-1234',
            'address' => 'Bandung',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Supplier::where('name', 'PT Sumber Makmur')->exists())->toBeTrue();
});

it('requires a supplier name', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('validates supplier email format', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'Supplier X',
            'email' => 'bukan-email',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'email']);
});

it('edits a supplier', function () {
    $supplier = Supplier::factory()->create(['name' => 'Lama']);

    livewire(EditSupplier::class, ['record' => $supplier->getRouteKey()])
        ->fillForm(['name' => 'Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($supplier->refresh()->name)->toBe('Baru');
});

it('shows only the products of the supplier in the relation manager', function () {
    $supplier = Supplier::factory()->create();
    $ownProducts = Product::factory()->count(2)->create(['supplier_id' => $supplier->id]);
    $otherProduct = Product::factory()->create();

    livewire(ProductsRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => EditSupplier::class,
    ])
        ->assertCanSeeTableRecords($ownProducts)
        ->assertCanNotSeeTableRecords([$otherProduct]);
});

it('has a products relationship', function () {
    $supplier = Supplier::factory()->create();
    Product::factory()->count(3)->create(['supplier_id' => $supplier->id]);

    expect($supplier->products)->toHaveCount(3);
});
