<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Pastikan permission Shield + 3 role sudah ada (idempotent, sekali per test).
 */
function ensureRolesSeeded(): void
{
    if (Permission::query()->count() === 0) {
        test()->seed(ShieldSeeder::class);
        test()->seed(RoleSeeder::class);
    }
}

/**
 * Buat user dengan role yang benar-benar punya permission Shield (TANPA login).
 */
function userWithRole(string $role): User
{
    ensureRolesSeeded();

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * User super admin (role `admin`) — memegang semua permission, tetapi tidak
 * melewati Policy (Shield `define_via_gate = false`, tanpa Gate::before):
 * syarat lain di Policy dan ability kustom tetap berlaku.
 * Dikembalikan tanpa login; pakai `$this->actingAs(...)` di test.
 */
function adminUser(): User
{
    ensureRolesSeeded();

    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

/**
 * Produk milik supplier (atau tanpa supplier) dengan stok awal dibentuk lewat
 * movement IN, bukan menulis `current_stock`. Reorder point 10: stok <= 10 = kritis.
 *
 * @param  array<string, mixed>  $attributes
 */
function productWithStockFor(?Supplier $supplier, int $stock, array $attributes = []): Product
{
    $product = Product::factory()->create([
        'supplier_id' => $supplier?->id,
        'reorder_point' => 10,
        ...$attributes,
    ]);

    StockMovement::factory()->for($product)->in($stock)->create();

    return $product->refresh();
}

/**
 * PO dengan satu item produk kritis milik supplier PO. Status selain draft dipasang
 * lewat query builder agar setup tidak melewati penjagaan transisi model.
 *
 * @param  array<string, mixed>  $attributes
 */
function purchaseOrderWithItem(string $status = 'draft', array $attributes = []): PurchaseOrder
{
    $supplier = Supplier::factory()->create();

    $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create($attributes);

    PurchaseOrderItem::factory()->for($purchaseOrder)->create([
        'product_id' => productWithStockFor($supplier, 5)->id,
    ]);

    PurchaseOrder::query()->whereKey($purchaseOrder->id)->update(['status' => $status]);

    return $purchaseOrder->refresh();
}

/**
 * PO dengan satu item per jumlah di `$quantities`; tiap item memakai produk kritis
 * baru (stok awal 5 lewat movement IN) milik supplier PO. Status dipasang lewat
 * query builder seperti `purchaseOrderWithItem()`.
 *
 * @param  list<int>  $quantities
 * @param  array<string, mixed>  $attributes
 */
function purchaseOrderWithItems(string $status, array $quantities, array $attributes = []): PurchaseOrder
{
    $supplier = Supplier::factory()->create();

    $purchaseOrder = PurchaseOrder::factory()->for($supplier)->create($attributes);

    foreach ($quantities as $quantity) {
        PurchaseOrderItem::factory()->for($purchaseOrder)->create([
            'product_id' => productWithStockFor($supplier, 5)->id,
            'qty' => $quantity,
        ]);
    }

    PurchaseOrder::query()->whereKey($purchaseOrder->id)->update(['status' => $status]);

    return $purchaseOrder->refresh()->load('items.product');
}

/**
 * Jalankan keputusan domain `approve` atau `reject` (dengan catatan) atas PO,
 * agar dataset keputusan di test persetujuan memakai satu jalur pemanggilan.
 */
function decidePurchaseOrder(PurchaseOrder $purchaseOrder, string $decision): void
{
    if ($decision === 'approve') {
        $purchaseOrder->approve();

        return;
    }

    $purchaseOrder->reject('Catatan penolakan');
}
