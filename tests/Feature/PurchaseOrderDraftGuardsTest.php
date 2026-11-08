<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

dataset('locked purchase order statuses', ['submitted', 'approved', 'rejected', 'received']);

it('[P0] rejects illegal status transitions in the domain even for admin', function (string $from, string $to) {
    $purchaseOrder = purchaseOrderWithItem($from);
    $activityCountBefore = Activity::query()->where('subject_type', PurchaseOrder::class)->count();
    $this->actingAs(adminUser());

    $purchaseOrder->status = PurchaseOrderStatus::from($to);

    expect(fn () => $purchaseOrder->save())->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status->value)->toBe($from)
        ->and(Activity::query()->where('subject_type', PurchaseOrder::class)->count())->toBe($activityCountBefore);
})->with([
    'draft to approved' => ['draft', 'approved'],
    'draft to rejected' => ['draft', 'rejected'],
    'draft to received' => ['draft', 'received'],
    'submitted to draft' => ['submitted', 'draft'],
    'submitted to received' => ['submitted', 'received'],
    'approved to draft' => ['approved', 'draft'],
    'approved to submitted' => ['approved', 'submitted'],
    'approved to rejected' => ['approved', 'rejected'],
    'rejected to draft' => ['rejected', 'draft'],
    'rejected to submitted' => ['rejected', 'submitted'],
    'rejected to approved' => ['rejected', 'approved'],
    'rejected to received' => ['rejected', 'received'],
    'received to draft' => ['received', 'draft'],
    'received to submitted' => ['received', 'submitted'],
    'received to approved' => ['received', 'approved'],
    'received to rejected' => ['received', 'rejected'],
]);

it('[P0] blocks adding and removing items of a non-draft purchase order even for admin', function (string $status) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $item = $purchaseOrder->items()->sole();
    $anotherSupplierProduct = productWithStockFor($purchaseOrder->supplier, 5);
    $this->actingAs(adminUser());

    expect(fn () => $purchaseOrder->items()->create([
        'product_id' => $anotherSupplierProduct->id,
        'qty' => 2,
        'unit_price' => 1_000,
    ]))->toThrow(AuthorizationException::class);

    expect(fn () => $item->delete())->toThrow(AuthorizationException::class);

    expect($purchaseOrder->items()->pluck('id')->all())->toBe([$item->id]);
})->with('locked purchase order statuses');

it('[P0] rejects a direct submit by staff in the domain', function () {
    $purchaseOrder = purchaseOrderWithItem();
    $this->actingAs(userWithRole('staff'));

    expect(fn () => $purchaseOrder->submit())->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Draft);
});

it('[P0] authorizes purchase order abilities by role and status', function (string $role, string $status, string $ability, bool $expected) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $user = userWithRole($role);

    expect($user->can($ability, $purchaseOrder))->toBe($expected);
})->with([
    'manager updates a draft' => ['manager', 'draft', 'update', true],
    'manager submits a draft' => ['manager', 'draft', 'submit', true],
    'manager deletes a draft' => ['manager', 'draft', 'delete', true],
    'manager cannot update a submitted order' => ['manager', 'submitted', 'update', false],
    'manager cannot submit a submitted order' => ['manager', 'submitted', 'submit', false],
    'manager cannot delete a submitted order' => ['manager', 'submitted', 'delete', false],
    'staff cannot update a draft' => ['staff', 'draft', 'update', false],
    'staff cannot submit a draft' => ['staff', 'draft', 'submit', false],
    'staff cannot delete a draft' => ['staff', 'draft', 'delete', false],
    'staff views a submitted order' => ['staff', 'submitted', 'view', true],
]);

it('[P0] forbids the edit page of a non-draft purchase order even for admin', function (string $status) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $this->actingAs(adminUser());

    livewire(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertForbidden();
})->with('locked purchase order statuses');

it('[P1] offers no submit or edit action on a non-draft purchase order', function () {
    $purchaseOrder = purchaseOrderWithItem('submitted');
    $this->actingAs(userWithRole('manager'));

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertActionHidden('submit')
        ->assertActionHidden('edit');

    livewire(ListPurchaseOrders::class)
        ->assertActionVisible(TestAction::make('view')->table($purchaseOrder))
        ->assertActionHidden(TestAction::make('edit')->table($purchaseOrder));
});

it('[P1] notifies the manager and keeps the draft when submitting a purchase order without items', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = PurchaseOrder::factory()->create(['created_by' => $manager->id]);
    $this->actingAs($manager);

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('submit')
        ->assertNotified('Purchase order gagal diajukan');

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Draft);
});

it('[P1] creates a draft from the real item repeater with the critical products at harga_modal', function () {
    $manager = userWithRole('manager');
    $this->actingAs($manager);
    $supplier = Supplier::factory()->create();
    $firstCriticalProduct = productWithStockFor($supplier, 3, ['harga_modal' => 12_500]);
    $secondCriticalProduct = productWithStockFor($supplier, 8, ['harga_modal' => 4_000]);
    productWithStockFor($supplier, 80);

    $component = livewire(CreatePurchaseOrder::class)
        ->fillForm(['supplier_id' => $supplier->id]);

    foreach (array_keys($component->get('data.items')) as $itemKey) {
        $component->set("data.items.{$itemKey}.qty", 7);
    }

    $component
        ->call('create')
        ->assertHasNoFormErrors();

    $purchaseOrder = PurchaseOrder::sole();
    $savedItems = $purchaseOrder->items()->orderBy('product_id')->get();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($purchaseOrder->created_by)->toBe($manager->id)
        ->and($savedItems->pluck('product_id')->all())->toBe([$firstCriticalProduct->id, $secondCriticalProduct->id])
        ->and($savedItems->pluck('qty')->all())->toBe([7, 7])
        ->and($savedItems->map(fn (PurchaseOrderItem $item): float => (float) $item->unit_price)->all())->toBe([12_500.0, 4_000.0]);
});

it('[P1] keeps the purchase order number fixed after creation even for admin', function () {
    $purchaseOrder = purchaseOrderWithItem();
    $originalNumber = $purchaseOrder->number;
    $this->actingAs(adminUser());

    $purchaseOrder->number = 'PO-209901-0001';

    expect(fn () => $purchaseOrder->save())->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->number)->toBe($originalNumber);
});

it('[P1] always starts a purchase order as draft even when another status is supplied', function () {
    $this->actingAs(adminUser());

    $purchaseOrder = new PurchaseOrder;
    $purchaseOrder->forceFill([
        'supplier_id' => Supplier::factory()->create()->id,
        'status' => PurchaseOrderStatus::Approved,
    ])->save();

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Draft);
});

it('[P1] rejects changing an item to another order or to a product outside the supplier', function (array $changedAttributes) {
    $purchaseOrder = purchaseOrderWithItem();
    $item = $purchaseOrder->items()->sole();
    $originalProductId = $item->product_id;
    $this->actingAs(adminUser());

    expect(fn () => $item->forceFill($changedAttributes)->save())->toThrow(AuthorizationException::class);

    expect($item->refresh()->purchase_order_id)->toBe($purchaseOrder->id)
        ->and($item->product_id)->toBe($originalProductId);
})->with([
    'moved to another purchase order' => fn (): array => ['purchase_order_id' => purchaseOrderWithItem()->id],
    'product of another supplier' => fn (): array => ['product_id' => productWithStockFor(Supplier::factory()->create(), 5)->id],
    'product without supplier' => fn (): array => ['product_id' => productWithStockFor(null, 5)->id],
]);

it('[P1] totals a purchase order as the sum of quantity times unit price', function () {
    $supplier = Supplier::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
    PurchaseOrderItem::factory()->for($purchaseOrder)->create([
        'product_id' => productWithStockFor($supplier, 5)->id,
        'qty' => 3,
        'unit_price' => 12_500,
    ]);
    PurchaseOrderItem::factory()->for($purchaseOrder)->create([
        'product_id' => productWithStockFor($supplier, 5)->id,
        'qty' => 2,
        'unit_price' => 1_250.50,
    ]);

    expect($purchaseOrder->refresh()->total())->toBe(40_001.0);
});

it('[P2] replaces the initial items when the manager switches supplier on the create form', function () {
    Repeater::fake();
    $this->actingAs(userWithRole('manager'));
    $firstSupplier = Supplier::factory()->create();
    $secondSupplier = Supplier::factory()->create();
    productWithStockFor($firstSupplier, 5);
    $secondSupplierCriticalProduct = productWithStockFor($secondSupplier, 5);

    livewire(CreatePurchaseOrder::class)
        ->fillForm(['supplier_id' => $firstSupplier->id])
        ->fillForm(['supplier_id' => $secondSupplier->id])
        ->assertSchemaStateSet(function (array $state) use ($secondSupplierCriticalProduct): void {
            expect(collect($state['items'])->pluck('product_id')->values()->all())
                ->toEqual([$secondSupplierCriticalProduct->id]);
        });
});

it('[P2] fills the item price with harga_modal when the manager picks another product of the supplier', function () {
    Repeater::fake();
    $this->actingAs(userWithRole('manager'));
    $supplier = Supplier::factory()->create();
    productWithStockFor($supplier, 5, ['harga_modal' => 12_500]);
    $nonCriticalProduct = productWithStockFor($supplier, 80, ['harga_modal' => 7_750]);

    livewire(CreatePurchaseOrder::class)
        ->fillForm(['supplier_id' => $supplier->id])
        ->set('data.items.0.product_id', $nonCriticalProduct->id)
        ->assertSchemaStateSet(function (array $state): void {
            expect((float) $state['items'][0]['unit_price'])->toBe(7_750.0);
        });
});

it('[P2] lets the manager update draft items on the edit page while the supplier stays locked', function () {
    Repeater::fake();
    $purchaseOrder = purchaseOrderWithItem();
    $productId = $purchaseOrder->items()->sole()->product_id;
    $this->actingAs(userWithRole('manager'));

    livewire(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormFieldDisabled('supplier_id')
        ->fillForm(['items' => [['product_id' => $productId, 'qty' => 9, 'unit_price' => 9_000]]])
        ->call('save')
        ->assertHasNoFormErrors();

    $savedItem = $purchaseOrder->items()->sole();

    expect($savedItem->product_id)->toBe($productId)
        ->and($savedItem->qty)->toBe(9)
        ->and((float) $savedItem->unit_price)->toBe(9_000.0);
});

it('[P3] shows item subtotals and the order total in Indonesian rupiah to the manager', function () {
    $supplier = Supplier::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create();
    PurchaseOrderItem::factory()->for($purchaseOrder)->create([
        'product_id' => productWithStockFor($supplier, 5)->id,
        'qty' => 3,
        'unit_price' => 12_500,
    ]);
    PurchaseOrderItem::factory()->for($purchaseOrder)->create([
        'product_id' => productWithStockFor($supplier, 5)->id,
        'qty' => 2,
        'unit_price' => 1_000,
    ]);
    $this->actingAs(userWithRole('manager'));

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertSeeText('37.500,00')
        ->assertSeeText('2.000,00')
        ->assertSeeText('39.500,00');
});
