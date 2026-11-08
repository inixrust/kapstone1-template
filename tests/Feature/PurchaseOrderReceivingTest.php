<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\StockMovements\Pages\EditStockMovement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

dataset('unreceivable purchase order statuses', ['draft', 'submitted', 'rejected', 'received']);

it('[P0] AC-1 creates one in movement per item referencing the purchase order when staff receives it', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [7, 3]);
    $staff = userWithRole('staff');
    $this->actingAs($staff);

    $purchaseOrder->receive();

    $movements = StockMovement::query()
        ->where('purchase_order_id', $purchaseOrder->id)
        ->get();

    expect($movements->pluck('qty', 'product_id')->all())->toEqual($purchaseOrder->items->pluck('qty', 'product_id')->all())
        ->and($movements)->toHaveCount(2)
        ->and($movements->pluck('type')->unique()->all())->toEqual([StockMovementType::In])
        ->and($movements->pluck('user_id')->unique()->all())->toEqual([$staff->id])
        ->and($movements->every(fn (StockMovement $movement): bool => str_contains((string) $movement->note, $purchaseOrder->number)))->toBeTrue();
});

it('[P0] AC-2 increases each product current stock by exactly the received quantity without changing its cost', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [7, 3]);
    $stockBefore = $purchaseOrder->items->mapWithKeys(fn (PurchaseOrderItem $item): array => [$item->product_id => $item->product->current_stock]);
    $costBefore = $purchaseOrder->items->mapWithKeys(fn (PurchaseOrderItem $item): array => [$item->product_id => $item->product->harga_modal]);
    $this->actingAs(userWithRole('staff'));

    $purchaseOrder->receive();

    $purchaseOrder->items->each(function (PurchaseOrderItem $item) use ($stockBefore, $costBefore): void {
        $product = $item->product->refresh();

        expect($product->current_stock)->toBe($stockBefore[$item->product_id] + $item->qty)
            ->and($product->harga_modal)->toBe($costBefore[$item->product_id]);
    });
});

it('[P0] AC-3 marks the purchase order as received when staff confirms the receive action', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [4]);
    $this->actingAs(userWithRole('staff'));

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('receive');

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Received);
});

it('[P0] AC-4 rejects receiving a purchase order that is not approved without new movements even for admin', function (string $status) {
    $purchaseOrder = purchaseOrderWithItems($status, [6]);
    $product = $purchaseOrder->items->first()->product;
    $movementCountBefore = StockMovement::query()->count();
    $this->actingAs(adminUser());

    expect(fn () => $purchaseOrder->receive())->toThrow(AuthorizationException::class);

    expect(StockMovement::query()->count())->toBe($movementCountBefore)
        ->and($product->refresh()->current_stock)->toBe(5)
        ->and($purchaseOrder->refresh()->status->value)->toBe($status);
})->with('unreceivable purchase order statuses');

it('[P0] AC-5 rolls back every movement and keeps the purchase order approved when one item fails', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [7, 3]);
    $stockBefore = $purchaseOrder->items->mapWithKeys(fn (PurchaseOrderItem $item): array => [$item->product_id => $item->product->current_stock]);
    $failingProductId = $purchaseOrder->items->last()->product_id;

    StockMovement::creating(function (StockMovement $movement) use ($failingProductId): void {
        if ($movement->product_id === $failingProductId) {
            throw new RuntimeException('Simulasi kegagalan penerimaan item');
        }
    });

    $this->actingAs(userWithRole('staff'));

    expect(fn () => $purchaseOrder->receive())->toThrow(RuntimeException::class, 'Simulasi kegagalan penerimaan item');

    expect(StockMovement::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(0)
        ->and($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Approved);

    $purchaseOrder->items->each(function (PurchaseOrderItem $item) use ($stockBefore): void {
        expect($item->product->refresh()->current_stock)->toBe($stockBefore[$item->product_id]);
    });
});

it('[P0] AC-6 forbids updating or deleting a movement that references a purchase order even for admin', function (string $operation) {
    $purchaseOrder = purchaseOrderWithItems('received', [7]);
    $product = $purchaseOrder->items->first()->product;
    $movement = StockMovement::factory()->for($product)->in(7)->create(['purchase_order_id' => $purchaseOrder->id]);
    $this->actingAs(adminUser());

    $changeMovement = match ($operation) {
        'update' => fn () => $movement->update(['qty' => 1]),
        'delete' => fn () => $movement->delete(),
    };

    expect($changeMovement)->toThrow(AuthorizationException::class);

    expect($movement->fresh()?->qty)->toBe(7)
        ->and($product->refresh()->current_stock)->toBe(12);
})->with(['update', 'delete']);

it('[P0] AC-7 denies staff and manager editing or deleting a movement that references a purchase order', function (string $role) {
    $purchaseOrder = purchaseOrderWithItems('received', [7]);
    $movement = StockMovement::factory()
        ->for($purchaseOrder->items->first()->product)
        ->in(7)
        ->create(['purchase_order_id' => $purchaseOrder->id]);
    $user = userWithRole($role);
    $this->actingAs($user);

    expect($user->can('update', $movement))->toBeFalse()
        ->and($user->can('delete', $movement))->toBeFalse();

    livewire(EditStockMovement::class, ['record' => $movement->getRouteKey()])
        ->assertForbidden();
})->with(['staff', 'manager']);

it('[P1] AC-8 shows the receive action to manager and staff only on approved purchase orders', function (string $role) {
    $approvedPurchaseOrder = purchaseOrderWithItems('approved', [4]);
    $submittedPurchaseOrder = purchaseOrderWithItems('submitted', [4]);
    $this->actingAs(userWithRole($role));

    livewire(ViewPurchaseOrder::class, ['record' => $approvedPurchaseOrder->getRouteKey()])
        ->assertActionVisible('receive');

    livewire(ViewPurchaseOrder::class, ['record' => $submittedPurchaseOrder->getRouteKey()])
        ->assertActionHidden('receive');
})->with(['manager', 'staff']);

it('[P0] AC-9 logs the receive transition with the receiving user as causer', function () {
    $purchaseOrder = purchaseOrderWithItems('approved', [4]);
    $staff = userWithRole('staff');
    $this->actingAs($staff);

    $purchaseOrder->receive();

    $activity = Activity::query()
        ->where('subject_type', PurchaseOrder::class)
        ->where('subject_id', $purchaseOrder->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity?->properties['attributes']['status'] ?? null)->toBe('received')
        ->and($activity->properties['old']['status'])->toBe('approved')
        ->and($activity->causer_id)->toBe($staff->id)
        ->and($activity->log_name)->toBe('purchase_order');
});
