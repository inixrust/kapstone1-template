<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\PurchaseOrder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

dataset('purchase order decisions', ['approve', 'reject']);

dataset('undecidable purchase order statuses', ['draft', 'approved', 'rejected', 'received']);

it('[P0] AC-1 lets an admin other than the creator approve a submitted purchase order without a note', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => $manager->id]);
    $admin = adminUser();
    $this->actingAs($admin);

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('approve');

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Approved)
        ->and($purchaseOrder->decided_by)->toBe($admin->id);
});

it('[P0] AC-2 lets an admin other than the creator reject a submitted purchase order with a note', function () {
    $manager = userWithRole('manager');
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => $manager->id]);
    $this->actingAs(adminUser());

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('reject', data: ['note' => 'Harga di atas anggaran bulan ini']);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Rejected)
        ->and($purchaseOrder->decision_note)->toBe('Harga di atas anggaran bulan ini');
});

it('[P1] AC-3 requires a note when rejecting a purchase order', function () {
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $this->actingAs(adminUser());

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction('reject', data: ['note' => ''])
        ->assertHasActionErrors(['note' => 'required']);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Submitted);
});

it('[P0] AC-4 forbids the creator from deciding their own purchase order even when the creator is admin', function (string $decision) {
    $admin = adminUser();
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => $admin->id]);
    $this->actingAs($admin);
    $activityCountBefore = Activity::query()->count();

    expect(fn () => decidePurchaseOrder($purchaseOrder, $decision))->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Submitted)
        ->and(Activity::query()->count())->toBe($activityCountBefore);
})->with('purchase order decisions');

it('[P0] AC-5 forbids manager and staff from deciding a purchase order', function (string $role, string $decision) {
    $purchaseOrder = purchaseOrderWithItem('submitted');
    $this->actingAs(userWithRole($role));

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertActionHidden($decision);

    expect(fn () => decidePurchaseOrder($purchaseOrder, $decision))->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status)->toBe(PurchaseOrderStatus::Submitted);
})->with(['manager', 'staff'])
    ->with('purchase order decisions');

it('[P0] AC-6 rejects deciding a purchase order that is not submitted even for admin', function (string $status, string $decision) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $this->actingAs(adminUser());

    expect(fn () => decidePurchaseOrder($purchaseOrder, $decision))->toThrow(AuthorizationException::class);

    expect($purchaseOrder->refresh()->status->value)->toBe($status);
})->with('undecidable purchase order statuses')
    ->with('purchase order decisions');

it('[P0] AC-7 logs the approve and reject transitions with the deciding admin as causer', function (string $decision, string $expectedStatus) {
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $admin = adminUser();
    $this->actingAs($admin);

    decidePurchaseOrder($purchaseOrder, $decision);

    $activity = Activity::query()
        ->where('subject_type', PurchaseOrder::class)
        ->where('subject_id', $purchaseOrder->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity?->properties['attributes']['status'] ?? null)->toBe($expectedStatus)
        ->and($activity->properties['old']['status'])->toBe('submitted')
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->log_name)->toBe('purchase_order');
})->with([
    'approve' => ['approve', 'approved'],
    'reject' => ['reject', 'rejected'],
]);
