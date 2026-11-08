<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\StockMovements\Pages\EditStockMovement;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

it('[P0] authorizes receiving by role and status', function (string $role, string $status, bool $expected) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $user = userWithRole($role);

    expect($user->can('receive', $purchaseOrder))->toBe($expected);
})->with([
    'admin on an approved order' => ['admin', 'approved', true],
    'manager on an approved order' => ['manager', 'approved', true],
    'staff on an approved order' => ['staff', 'approved', true],
    'admin on a draft' => ['admin', 'draft', false],
    'manager on a submitted order' => ['manager', 'submitted', false],
    'staff on a rejected order' => ['staff', 'rejected', false],
    'staff on a received order' => ['staff', 'received', false],
]);

it('[P0] refuses a second receipt from a stale copy of an already received purchase order', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [7, 3]);
    $staleCopy = PurchaseOrder::query()->findOrFail($purchaseOrder->id);
    $this->actingAs(userWithRole('staff'));

    $purchaseOrder->receive();

    expect(fn () => $staleCopy->receive())->toThrow(AuthorizationException::class);

    expect(StockMovement::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(2);

    expect($purchaseOrder->items->map(fn (PurchaseOrderItem $item): int => $item->product->refresh()->current_stock)->all())
        ->toBe([12, 8]);
});

it('[P0] keeps purchase order movements when manager bulk deletes them with other movements', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [7]);
    $this->actingAs(userWithRole('staff'));
    $purchaseOrder->receive();
    $receivedProduct = $purchaseOrder->items->first()->product;
    $purchaseOrderMovement = StockMovement::query()->where('purchase_order_id', $purchaseOrder->id)->sole();
    $manualMovement = StockMovement::factory()->out(2)->create();
    $this->actingAs(userWithRole('manager'));

    livewire(ListStockMovements::class)
        ->callTableBulkAction('delete', [$purchaseOrderMovement, $manualMovement]);

    expect(StockMovement::query()->find($purchaseOrderMovement->id))->not->toBeNull()
        ->and(StockMovement::query()->find($manualMovement->id))->toBeNull()
        ->and($receivedProduct->refresh()->current_stock)->toBe(12);
});

it('[P0] records only the status in receive activity, never prices', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [4]);
    $this->actingAs(userWithRole('staff'));

    $purchaseOrder->receive();

    $receiveActivity = Activity::query()
        ->where('subject_type', PurchaseOrder::class)
        ->where('subject_id', $purchaseOrder->id)
        ->where('event', 'updated')
        ->sole();

    expect(array_keys($receiveActivity->properties['attributes']))->toBe(['status'])
        ->and(array_keys($receiveActivity->properties['old']))->toBe(['status']);
});

it('[P1] refuses receiving without a logged-in user', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [4]);

    expect(fn () => $purchaseOrder->receive())->toThrow(AuthorizationException::class);

    expect(StockMovement::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(0)
        ->and($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Approved);
});

it('[P1] notifies success and removes the receive action once staff receives the purchase order', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [4]);
    $this->actingAs(userWithRole('staff'));

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('receive')
        ->assertNotified('Purchase order diterima')
        ->assertActionHidden('receive');
});

it('[P1] leaves products and purchase orders outside the received order untouched', function () {
    $receivedPurchaseOrder = purchaseOrderWithItems('approved', [7]);
    $otherPurchaseOrder = purchaseOrderWithItems('approved', [9]);
    $this->actingAs(userWithRole('staff'));

    $receivedPurchaseOrder->receive();

    expect($otherPurchaseOrder->items->first()->product->refresh()->current_stock)->toBe(5)
        ->and($otherPurchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Approved)
        ->and($otherPurchaseOrder->stockMovements()->count())->toBe(0);
});

it('[P1] still lets users edit and delete movements without a purchase order per the existing policy', function (string $role, string $ability, bool $expected) {
    $movement = StockMovement::factory()->in(7)->create();
    $user = userWithRole($role);

    expect($user->can($ability, $movement))->toBe($expected);
})->with([
    'staff updates' => ['staff', 'update', true],
    'staff cannot delete' => ['staff', 'delete', false],
    'manager updates' => ['manager', 'update', true],
    'manager deletes' => ['manager', 'delete', true],
]);

it('[P1] replays stock when admin updates and deletes a movement without a purchase order', function () {
    $product = productWithStockFor(null, 5);
    $movement = StockMovement::factory()->for($product)->in(7)->create();
    $this->actingAs(adminUser());

    $movement->update(['qty' => 2]);

    expect($product->refresh()->current_stock)->toBe(7);

    $movement->delete();

    expect($product->refresh()->current_stock)->toBe(5);
});

it('[P1] opens the edit page of a movement without a purchase order for staff', function () {
    $movement = StockMovement::factory()->in(7)->create();
    $this->actingAs(userWithRole('staff'));

    livewire(EditStockMovement::class, ['record' => $movement->getRouteKey()])
        ->assertOk();
});

it('[P2] records the purchase order and receiving user on each received movement activity', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [4]);
    $staff = userWithRole('staff');
    $this->actingAs($staff);

    $purchaseOrder->receive();

    $movement = $purchaseOrder->stockMovements()->sole();

    $movementActivity = Activity::query()
        ->where('subject_type', StockMovement::class)
        ->where('subject_id', $movement->id)
        ->sole();

    expect($movementActivity->attribute_changes['attributes']['purchase_order_id'])->toBe($purchaseOrder->id)
        ->and($movementActivity->causer_id)->toBe($staff->id)
        ->and($movementActivity->log_name)->toBe('stock_movement');
});

it('[P2] lets the manager who created the purchase order receive it', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = purchaseOrderWithItems('approved', [4], ['created_by' => $manager->id]);
    $this->actingAs($manager);

    $purchaseOrder->receive();

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Received);
});

it('[P3] receives a purchase order with fifty items in one transaction', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', array_fill(0, 50, 2));
    $this->actingAs(userWithRole('staff'));

    $purchaseOrder->receive();

    expect($purchaseOrder->stockMovements()->count())->toBe(50)
        ->and($purchaseOrder->items->every(fn (PurchaseOrderItem $item): bool => $item->product->refresh()->current_stock === 7))->toBeTrue();
});
