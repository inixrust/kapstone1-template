<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

use function Pest\Livewire\livewire;

dataset('non-draft purchase order statuses', ['submitted', 'approved', 'rejected', 'received']);

it('[P3] AC-1 shows the purchase order status with its Indonesian label', function () {
    $purchaseOrder = purchaseOrderWithItem('submitted');
    $this->actingAs(userWithRole('manager'));

    livewire(ListPurchaseOrders::class)
        ->assertTableColumnFormattedStateSet('status', 'Diajukan', $purchaseOrder);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P1] AC-2 numbers purchase orders PO-YYYYMM-#### sequentially per month as drafts', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager);
    $supplier = Supplier::factory()->create();

    $this->travelTo(Carbon::parse('2026-11-08 09:00'));
    PurchaseOrder::create(['supplier_id' => $supplier->id]);
    $secondNovemberOrder = PurchaseOrder::create(['supplier_id' => $supplier->id]);

    $this->travelTo(Carbon::parse('2026-12-01 08:00'));
    $firstDecemberOrder = PurchaseOrder::create(['supplier_id' => $supplier->id]);

    expect($secondNovemberOrder->number)->toBe('PO-202611-0002')
        ->and($firstDecemberOrder->number)->toBe('PO-202612-0001')
        ->and($firstDecemberOrder->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($firstDecemberOrder->created_by)->toBe($manager->id);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P1] AC-3 offers only the critical products of the selected supplier as initial items', function () {
    Repeater::fake();
    $this->actingAs(userWithRole('manager'));
    $supplier = Supplier::factory()->create();
    $criticalProduct = productWithStockFor($supplier, 5);
    productWithStockFor($supplier, 50);
    productWithStockFor(Supplier::factory()->create(), 5);
    productWithStockFor(null, 5);

    livewire(CreatePurchaseOrder::class)
        ->fillForm(['supplier_id' => $supplier->id])
        ->assertSchemaStateSet(function (array $state) use ($criticalProduct): void {
            expect(collect($state['items'])->pluck('product_id')->values()->all())
                ->toEqual([$criticalProduct->id]);
        });
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P1] AC-4 defaults the item price to harga_modal and lets the manager change it', function () {
    Repeater::fake();
    $this->actingAs(userWithRole('manager'));
    $supplier = Supplier::factory()->create();
    $product = productWithStockFor($supplier, 5, ['harga_modal' => 12_500]);

    livewire(CreatePurchaseOrder::class)
        ->fillForm(['supplier_id' => $supplier->id])
        ->assertSchemaStateSet(function (array $state): void {
            expect((float) collect($state['items'])->first()['unit_price'])->toBe(12_500.0);
        })
        ->fillForm(['items' => [['product_id' => $product->id, 'qty' => 20, 'unit_price' => 11_000]]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect((float) PurchaseOrderItem::sole()->unit_price)->toBe(11_000.0)
        ->and((float) $product->refresh()->harga_modal)->toBe(12_500.0);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P0] AC-5 lets the manager submit a draft purchase order', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = purchaseOrderWithItem(attributes: ['created_by' => $manager->id]);
    $this->actingAs($manager);

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('submit');

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Submitted);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P0] AC-9 forbids staff from creating or submitting a purchase order', function () {
    $purchaseOrder = purchaseOrderWithItem();
    $this->actingAs(userWithRole('staff'));

    livewire(CreatePurchaseOrder::class)
        ->assertForbidden();

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertActionHidden('submit');

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Draft);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P0] AC-10 rejects submitting a non-draft purchase order even for admin', function (string $status) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $this->actingAs(adminUser());

    expect(fn () => $purchaseOrder->submit())->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status->value)->toBe($status);
})->with('non-draft purchase order statuses')
    ->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P0] AC-11 locks items and deletion of a non-draft purchase order even for admin', function (string $status) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $item = $purchaseOrder->items()->sole();
    $originalUnitPrice = (float) $item->unit_price;
    $this->actingAs(adminUser());

    expect(fn () => $item->update(['unit_price' => 1]))->toThrow(AuthorizationException::class);

    expect(fn () => $purchaseOrder->delete())->toThrow(AuthorizationException::class);

    expect((float) $item->refresh()->unit_price)->toBe($originalUnitPrice)
        ->and(PurchaseOrder::query()->whereKey($purchaseOrder->id)->exists())->toBeTrue();
})->with('non-draft purchase order statuses')
    ->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P1] AC-12 only accepts items from the purchase order supplier, critical or not', function () {
    $purchaseOrder = purchaseOrderWithItem();
    $foreignProduct = productWithStockFor(Supplier::factory()->create(), 5);
    $nonCriticalProduct = productWithStockFor($purchaseOrder->supplier, 50);
    $this->actingAs(userWithRole('manager'));

    expect(fn () => $purchaseOrder->items()->create([
        'product_id' => $foreignProduct->id,
        'qty' => 3,
        'unit_price' => 1_000,
    ]))->toThrow(AuthorizationException::class);

    $purchaseOrder->items()->create([
        'product_id' => $nonCriticalProduct->id,
        'qty' => 3,
        'unit_price' => 1_000,
    ]);

    expect($purchaseOrder->items()->pluck('product_id')->all())
        ->toContain($nonCriticalProduct->id)
        ->not->toContain($foreignProduct->id);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P2] AC-14 lets the manager delete a draft purchase order', function () {
    $purchaseOrder = purchaseOrderWithItem();
    $this->actingAs(userWithRole('manager'));

    livewire(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($purchaseOrder);

    expect(PurchaseOrderItem::query()->where('purchase_order_id', $purchaseOrder->id)->exists())->toBeFalse();
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P2] AC-15 keeps the supplier of a purchase order fixed after the draft is created', function () {
    $purchaseOrder = purchaseOrderWithItem();
    $originalSupplierId = $purchaseOrder->supplier_id;
    $this->actingAs(userWithRole('manager'));

    expect(fn () => $purchaseOrder->update(['supplier_id' => Supplier::factory()->create()->id]))
        ->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->supplier_id)->toBe($originalSupplierId);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P2] AC-16 validates item quantity, price, and product uniqueness', function (int $qty, int $unitPrice, bool $duplicateProduct, string $erroredField) {
    Repeater::fake();
    $this->actingAs(userWithRole('manager'));
    $supplier = Supplier::factory()->create();
    $product = productWithStockFor($supplier, 5);
    $item = ['product_id' => $product->id, 'qty' => $qty, 'unit_price' => $unitPrice];

    livewire(CreatePurchaseOrder::class)
        ->fillForm(['supplier_id' => $supplier->id])
        ->fillForm(['items' => $duplicateProduct ? [$item, $item] : [$item]])
        ->call('create')
        ->assertHasFormErrors([$erroredField]);

    expect(PurchaseOrder::query()->count())->toBe(0);
})->with([
    'zero quantity' => [0, 1_000, false, 'items.0.qty'],
    'negative price' => [1, -1, false, 'items.0.unit_price'],
    'duplicate product' => [1, 1_000, true, 'items.1.product_id'],
])->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P1] AC-17 rejects submitting a purchase order without items', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = PurchaseOrder::factory()->create(['created_by' => $manager->id]);
    $this->actingAs($manager);

    expect(fn () => $purchaseOrder->submit())->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Draft);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');

it('[P1] AC-18 enforces a unique purchase order number in the database', function () {
    PurchaseOrder::factory()->create(['number' => 'PO-202611-0001']);

    expect(fn () => PurchaseOrder::factory()->create(['number' => 'PO-202611-0001']))
        ->toThrow(UniqueConstraintViolationException::class);
})->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');
