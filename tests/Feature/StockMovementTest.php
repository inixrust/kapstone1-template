<?php

use App\Enums\StockMovementType;
use App\Filament\Resources\StockMovements\Pages\CreateStockMovement;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Livewire\livewire;

it('increments current_stock on an in movement', function () {
    $product = Product::factory()->create();

    StockMovement::factory()->for($product)->in(100)->create();

    expect($product->refresh()->current_stock)->toBe(100);
});

it('decrements current_stock on an out movement', function () {
    $product = Product::factory()->create();

    StockMovement::factory()->for($product)->in(100)->create();
    StockMovement::factory()->for($product)->out(30)->create();

    expect($product->refresh()->current_stock)->toBe(70);
});

it('sets current_stock to the adjustment value', function () {
    $product = Product::factory()->create();

    StockMovement::factory()->for($product)->in(100)->create();
    StockMovement::factory()->for($product)->adjustment(42)->create();

    expect($product->refresh()->current_stock)->toBe(42);
});

it('recalculates current_stock when a movement is deleted', function () {
    $product = Product::factory()->create();

    StockMovement::factory()->for($product)->in(100)->create();
    $out = StockMovement::factory()->for($product)->out(40)->create();
    expect($product->refresh()->current_stock)->toBe(60);

    $out->delete();
    expect($product->refresh()->current_stock)->toBe(100);
});

it('records the authenticated user as the causer', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager);
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::In,
        'qty' => 10,
    ]);

    expect($movement->user_id)->toBe($manager->id);
});

it('forbids staff from creating an adjustment directly on the model', function () {
    $staff = userWithRole('staff');
    $this->actingAs($staff);
    $product = Product::factory()->create();

    expect(fn () => StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'qty' => 500,
    ]))->toThrow(AuthorizationException::class);

    expect(StockMovement::where('type', StockMovementType::Adjustment)->count())->toBe(0);
});

it('allows staff to create in and out movements', function () {
    $staff = userWithRole('staff');
    $this->actingAs($staff);
    $product = Product::factory()->create();

    $in = StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::In,
        'qty' => 20,
    ]);

    expect($in)->toBeInstanceOf(StockMovement::class)
        ->and($product->refresh()->current_stock)->toBe(20);
});

it('allows manager to create an adjustment', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager);
    $product = Product::factory()->create();

    StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'qty' => 250,
    ]);

    expect($product->refresh()->current_stock)->toBe(250);
});

it('does not offer the adjustment option to staff in the form', function () {
    $staff = userWithRole('staff');
    $this->actingAs($staff);
    $product = Product::factory()->create();

    livewire(CreateStockMovement::class)
        ->assertSuccessful()
        ->fillForm([
            'product_id' => $product->id,
            'type' => StockMovementType::Adjustment->value,
            'qty' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['type']);
});
