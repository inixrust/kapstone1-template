<?php

use App\Models\Product;
use App\Models\StockMovement;

/**
 * Matriks otorisasi menyeluruh: role × resource × ability.
 * Ini pemenuhan langsung guideline security Spatie soal automated test otorisasi.
 */
dataset('permission expectations', [
    // [role, permission, expected]

    // ── admin (super admin) boleh segalanya ────────────────────────────────
    ['admin', 'ViewAny:Product', true],
    ['admin', 'ForceDelete:Product', true],
    ['admin', 'DeleteAny:Category', true],
    ['admin', 'ViewAny:Role', true],
    ['admin', 'Delete:Role', true],
    ['admin', 'ForceDelete:StockMovement', true],

    // ── manager: kelola penuh domain KECUALI force-delete & role ────────────
    ['manager', 'ViewAny:Product', true],
    ['manager', 'Create:Product', true],
    ['manager', 'Update:Product', true],
    ['manager', 'Delete:Product', true],
    ['manager', 'ForceDelete:Product', false],
    ['manager', 'Delete:Category', true],
    ['manager', 'Delete:Supplier', true],
    ['manager', 'Delete:StockMovement', true],
    ['manager', 'ViewAny:Role', false],
    ['manager', 'Create:Role', false],

    // ── staff: lihat + input Product/StockMovement, TANPA delete ────────────
    ['staff', 'ViewAny:Product', true],
    ['staff', 'Create:Product', true],
    ['staff', 'Update:Product', true],
    ['staff', 'Delete:Product', false],
    ['staff', 'DeleteAny:Product', false],
    ['staff', 'ViewAny:StockMovement', true],
    ['staff', 'Create:StockMovement', true],
    ['staff', 'Delete:StockMovement', false],
    ['staff', 'ViewAny:Category', true],
    ['staff', 'Create:Category', false],
    ['staff', 'Delete:Category', false],
    ['staff', 'ViewAny:Supplier', true],
    ['staff', 'Create:Supplier', false],
    ['staff', 'ViewAny:Role', false],
]);

it('enforces the authorization matrix', function (string $role, string $permission, bool $expected) {
    $user = userWithRole($role);

    expect($user->can($permission))->toBe($expected);
})->with('permission expectations');

it('gives admin the super admin bypass for gate abilities', function () {
    $admin = userWithRole('admin');

    // ability kustom (bukan permission Shield) tetap lolos via Gate::before
    expect($admin->can('adjust', StockMovement::class))->toBeTrue()
        ->and($admin->can('viewCost', Product::class))->toBeTrue();
});

it('restricts custom abilities per role', function () {
    expect(userWithRole('staff')->can('adjust', StockMovement::class))->toBeFalse()
        ->and(userWithRole('manager')->can('adjust', StockMovement::class))->toBeTrue()
        ->and(userWithRole('staff')->can('viewCost', Product::class))->toBeFalse()
        ->and(userWithRole('manager')->can('viewCost', Product::class))->toBeTrue();
});

dataset('purchase order permission expectations', [
    ['manager', 'ViewAny:PurchaseOrder', true],
    ['manager', 'View:PurchaseOrder', true],
    ['manager', 'Create:PurchaseOrder', true],
    ['manager', 'Update:PurchaseOrder', true],
    ['manager', 'Delete:PurchaseOrder', true],
    ['manager', 'ForceDelete:PurchaseOrder', false],
    ['staff', 'ViewAny:PurchaseOrder', true],
    ['staff', 'View:PurchaseOrder', true],
    ['staff', 'Create:PurchaseOrder', false],
    ['staff', 'Update:PurchaseOrder', false],
    ['staff', 'Delete:PurchaseOrder', false],
]);

it('[P0] AC-6 enforces the purchase order authorization matrix', function (string $role, string $permission, bool $expected) {
    $user = userWithRole($role);

    expect($user->can($permission))->toBe($expected);
})->with('purchase order permission expectations')
    ->skip('ATDD red phase: story 1 purchase order belum diimplementasikan');
