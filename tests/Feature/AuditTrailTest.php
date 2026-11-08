<?php

use App\Enums\StockMovementType;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\StockActivitiesRelationManager;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

it('logs an activity with the causer when a movement is created', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager);
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::In,
        'qty' => 10,
    ]);

    $activity = Activity::query()
        ->where('subject_type', StockMovement::class)
        ->where('subject_id', $movement->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($manager->id)
        ->and($activity->log_name)->toBe('stock_movement')
        ->and($activity->description)->toContain('Pergerakan stok in created');
});

it('uses a special description for adjustment movements', function () {
    $admin = userWithRole('admin');
    $this->actingAs($admin);
    $product = Product::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'qty' => 42,
    ]);

    $activity = Activity::query()
        ->where('subject_id', $movement->id)
        ->where('subject_type', StockMovement::class)
        ->latest()
        ->first();

    expect($activity->description)->toContain('Penyesuaian stok (adjustment)')
        ->and($activity->description)->toContain('42');
});

it('exposes activity log per product', function () {
    $product = Product::factory()->create();
    StockMovement::factory()->for($product)->in(5)->create();
    StockMovement::factory()->for($product)->out(2)->create();

    // movement untuk produk lain tidak boleh ikut terhitung
    StockMovement::factory()->create();

    expect($product->stockActivities()->count())->toBe(2);
});

it('renders the audit relation manager on the product page', function () {
    $this->actingAs(adminUser());
    $product = Product::factory()->create();
    StockMovement::factory()->for($product)->in(5)->create();

    livewire(StockActivitiesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])->assertSuccessful();
});

it('[P0] AC-8 logs the submit transition with the causer and old and new status', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = purchaseOrderWithItem(attributes: ['created_by' => $manager->id]);
    $this->actingAs($manager);

    $purchaseOrder->submit();

    $activity = Activity::query()
        ->where('subject_type', PurchaseOrder::class)
        ->where('subject_id', $purchaseOrder->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity?->properties['attributes']['status'] ?? null)->toBe('submitted')
        ->and($activity->properties['old']['status'])->toBe('draft')
        ->and($activity->causer_id)->toBe($manager->id)
        ->and($activity->log_name)->toBe('purchase_order');
});

it('[P0] AC-13 records only the status in purchase order activity, never prices', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager);
    $purchaseOrder = PurchaseOrder::create(['supplier_id' => Supplier::factory()->create()->id]);
    $purchaseOrder->items()->create([
        'product_id' => productWithStockFor($purchaseOrder->supplier, 5)->id,
        'qty' => 4,
        'unit_price' => 25_000,
    ]);

    $purchaseOrder->submit();

    $loggedAttributes = Activity::query()
        ->where('subject_type', PurchaseOrder::class)
        ->where('subject_id', $purchaseOrder->id)
        ->get()
        ->flatMap(fn (Activity $activity): array => array_keys($activity->properties['attributes'] ?? []))
        ->unique()
        ->values()
        ->all();

    expect($loggedAttributes)->toBe(['status']);
});
