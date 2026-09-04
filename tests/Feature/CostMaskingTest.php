<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;

use function Pest\Livewire\livewire;

it('hides the harga_modal form field from staff', function () {
    $this->actingAs(userWithRole('staff'));

    livewire(CreateProduct::class)
        ->assertFormFieldHidden('harga_modal');
});

it('shows the harga_modal form field to manager and admin', function () {
    $this->actingAs(userWithRole('manager'));
    livewire(CreateProduct::class)
        ->assertFormFieldVisible('harga_modal');

    $this->actingAs(userWithRole('admin'));
    livewire(CreateProduct::class)
        ->assertFormFieldVisible('harga_modal');
});

it('hides the harga_modal table column from staff', function () {
    Product::factory()->count(2)->create();
    $this->actingAs(userWithRole('staff'));

    livewire(ListProducts::class)
        ->assertTableColumnHidden('harga_modal');
});

it('shows the harga_modal table column to manager', function () {
    Product::factory()->count(2)->create();
    $this->actingAs(userWithRole('manager'));

    livewire(ListProducts::class)
        ->assertTableColumnVisible('harga_modal');
});

it('grants viewCost only to manager and admin', function () {
    expect(userWithRole('staff')->can('viewCost', Product::class))->toBeFalse()
        ->and(userWithRole('manager')->can('viewCost', Product::class))->toBeTrue()
        ->and(userWithRole('admin')->can('viewCost', Product::class))->toBeTrue();
});
