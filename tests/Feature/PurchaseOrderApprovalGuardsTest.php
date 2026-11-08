<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

use function Pest\Livewire\livewire;

dataset('purchase order decision abilities', ['approve', 'reject']);

it('[P0] hides the approve and reject actions from the admin who created the purchase order', function () {
    $admin = adminUser();
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => $admin->id]);
    $this->actingAs($admin);

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject');
});

it('[P0] authorizes approving and rejecting by role and status', function (string $role, string $status, bool $expected, string $ability) {
    $purchaseOrder = purchaseOrderWithItem($status);
    $user = userWithRole($role);

    expect($user->can($ability, $purchaseOrder))->toBe($expected);
})->with([
    'admin on a submitted order' => ['admin', 'submitted', true],
    'admin on a draft' => ['admin', 'draft', false],
    'admin on an approved order' => ['admin', 'approved', false],
    'admin on a rejected order' => ['admin', 'rejected', false],
    'admin on a received order' => ['admin', 'received', false],
    'manager on a submitted order' => ['manager', 'submitted', false],
    'staff on a submitted order' => ['staff', 'submitted', false],
])->with('purchase order decision abilities');

it('[P0] records only the status in decision activity, never prices or the note', function (string $decision) {
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $this->actingAs(adminUser());

    decidePurchaseOrder($purchaseOrder, $decision);

    $decisionActivity = Activity::query()
        ->where('subject_type', PurchaseOrder::class)
        ->where('subject_id', $purchaseOrder->id)
        ->where('event', 'updated')
        ->sole();

    expect(array_keys($decisionActivity->properties['attributes']))->toBe(['status'])
        ->and(array_keys($decisionActivity->properties['old']))->toBe(['status'])
        ->and(json_encode($decisionActivity->properties))->not->toContain('Catatan penolakan');
})->with('purchase order decision abilities');

it('[P1] records the deciding admin, decision time and note and notifies success', function (string $decision, string $note, PurchaseOrderStatus $expectedStatus, ?string $expectedNote, string $expectedNotification) {
    $this->freezeSecond();
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $admin = adminUser();
    $this->actingAs($admin);

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->callAction($decision, data: ['note' => $note])
        ->assertHasNoActionErrors()
        ->assertNotified($expectedNotification);

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe($expectedStatus)
        ->and($purchaseOrder->decided_by)->toBe($admin->id)
        ->and($purchaseOrder->decided_at?->equalTo(now()))->toBeTrue()
        ->and($purchaseOrder->decision_note)->toBe($expectedNote);
})->with([
    'approve with a note' => ['approve', 'Sesuai anggaran kuartal', PurchaseOrderStatus::Approved, 'Sesuai anggaran kuartal', 'Purchase order disetujui'],
    'approve with a blank note' => ['approve', '', PurchaseOrderStatus::Approved, null, 'Purchase order disetujui'],
    'reject with a note' => ['reject', 'Supplier terlambat mengirim', PurchaseOrderStatus::Rejected, 'Supplier terlambat mengirim', 'Purchase order ditolak'],
]);

it('[P1] requires a rejection note in the domain, not only in the form', function (string $blankNote) {
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $this->actingAs(adminUser());
    $activityCountBefore = Activity::query()->count();

    expect(fn () => $purchaseOrder->reject($blankNote))->toThrow(AuthorizationException::class);

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Submitted)
        ->and($purchaseOrder->decided_by)->toBeNull()
        ->and($purchaseOrder->decided_at)->toBeNull()
        ->and($purchaseOrder->decision_note)->toBeNull()
        ->and(Activity::query()->count())->toBe($activityCountBefore);
})->with([
    'empty' => '',
    'whitespace only' => '   ',
]);

it('[P1] refuses a decision without a logged-in user', function (string $decision) {
    $purchaseOrder = purchaseOrderWithItem('submitted');

    expect(fn () => decidePurchaseOrder($purchaseOrder, $decision))->toThrow(AuthorizationException::class);

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Submitted)
        ->and($purchaseOrder->decided_by)->toBeNull();
})->with('purchase order decision abilities');

it('[P1] offers no approve or reject action on a purchase order that is not submitted', function (string $status) {
    $purchaseOrder = purchaseOrderWithItem($status, ['created_by' => userWithRole('manager')->id]);
    $this->actingAs(adminUser());

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject');
})->with(['draft', 'approved', 'rejected', 'received']);

it('[P2] shows the rejection decision and note to staff on the purchase order detail', function () {
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $admin = adminUser();
    $this->actingAs($admin);
    $purchaseOrder->reject('Stok gudang masih cukup untuk bulan ini');

    $this->actingAs(userWithRole('staff'));

    livewire(ViewPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertSuccessful()
        ->assertSeeText('Ditolak')
        ->assertSeeText($admin->name)
        ->assertSeeText('Stok gudang masih cukup untuk bulan ini');
});

it('[P2] ignores decision columns supplied through mass assignment', function () {
    $decider = adminUser();
    $this->actingAs(userWithRole('manager'));

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => Supplier::factory()->create()->id,
        'decided_by' => $decider->id,
        'decided_at' => now(),
        'decision_note' => 'Disetujui diam-diam',
    ])->refresh();

    expect($purchaseOrder->decided_by)->toBeNull()
        ->and($purchaseOrder->decided_at)->toBeNull()
        ->and($purchaseOrder->decision_note)->toBeNull();
});

it('[P3] keeps the decided purchase order when the deciding admin account is deleted', function () {
    $purchaseOrder = purchaseOrderWithItem('submitted', ['created_by' => userWithRole('manager')->id]);
    $admin = adminUser();
    $this->actingAs($admin);
    $purchaseOrder->approve();

    $admin->delete();

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Approved)
        ->and($purchaseOrder->decided_by)->toBeNull()
        ->and($purchaseOrder->decided_at)->not->toBeNull();
});
