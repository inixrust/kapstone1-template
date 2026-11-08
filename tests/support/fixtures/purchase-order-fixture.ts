import { randomUUID } from 'node:crypto';
import { encodeForPhp, runTinker } from '../helpers/artisan';
import { test as authTest } from './auth-fixture';

export const purchaseOrderDataPrefix = 'E2E PO';

export type CriticalProductData = {
    supplierId: number;
    supplierName: string;
    productId: number;
    productName: string;
};

type PurchaseOrderFixtures = {
    /**
     * Supplier + produk kritis (stok 0, reorder point 10) khusus test di database dev.
     * Tidak pernah memakai produk seed, karena movement hasil PO terkunci dan
     * tidak bisa dihapus lewat UI (R-012 di test design).
     */
    createCriticalProduct: () => Promise<CriticalProductData>;
    currentStockOf: (productId: number) => number;
    purchaseOrderNumberFor: (supplierId: number) => string;
};

/**
 * Menghapus lewat query builder disengaja: PO berstatus diterima dan movement
 * yang merujuk PO dikunci di domain, jadi teardown test tidak bisa lewat model.
 * Dipanggil sebelum dan sesudah test agar sisa run yang terhenti ikut bersih.
 */
function cleanupPurchaseOrderData(): void {
    runTinker(`
        $prefix = ${encodeForPhp(`${purchaseOrderDataPrefix} %`)};
        $supplierIds = DB::table('suppliers')->where('name', 'like', $prefix)->pluck('id');
        $categoryIds = DB::table('categories')->where('name', 'like', $prefix)->pluck('id');
        $productIds = DB::table('products')->whereIn('supplier_id', $supplierIds)->pluck('id');
        $purchaseOrderIds = DB::table('purchase_orders')->whereIn('supplier_id', $supplierIds)->pluck('id');
        $movementIds = DB::table('stock_movements')->whereIn('product_id', $productIds)->pluck('id');
        $subjects = [
            App\\Models\\PurchaseOrder::class => $purchaseOrderIds,
            App\\Models\\StockMovement::class => $movementIds,
            App\\Models\\Product::class => $productIds,
            App\\Models\\Supplier::class => $supplierIds,
            App\\Models\\Category::class => $categoryIds,
        ];
        foreach ($subjects as $subjectType => $subjectIds) {
            DB::table('activity_log')->where('subject_type', $subjectType)->whereIn('subject_id', $subjectIds)->delete();
        }
        DB::table('stock_movements')->whereIn('id', $movementIds)->delete();
        DB::table('purchase_orders')->whereIn('id', $purchaseOrderIds)->delete();
        DB::table('products')->whereIn('id', $productIds)->delete();
        DB::table('suppliers')->whereIn('id', $supplierIds)->delete();
        DB::table('categories')->whereIn('id', $categoryIds)->delete();
    `);
}

export const test = authTest.extend<PurchaseOrderFixtures>({
    createCriticalProduct: async ({}, use) => {
        cleanupPurchaseOrderData();

        await use(async () => {
            const suffix = randomUUID().slice(0, 8);
            const names = {
                category: `${purchaseOrderDataPrefix} Kategori ${suffix}`,
                supplier: `${purchaseOrderDataPrefix} Supplier ${suffix}`,
                product: `${purchaseOrderDataPrefix} Produk ${suffix}`,
                sku: `E2E-PO-${suffix}`,
            };

            const created = JSON.parse(
                runTinker(`
                    $names = ${encodeForPhp(names)};
                    $category = App\\Models\\Category::create(['name' => $names['category']]);
                    $supplier = App\\Models\\Supplier::create(['name' => $names['supplier']]);
                    $product = App\\Models\\Product::create([
                        'sku' => $names['sku'],
                        'name' => $names['product'],
                        'category_id' => $category->id,
                        'supplier_id' => $supplier->id,
                        'harga_jual' => 20000,
                        'harga_modal' => 15000,
                        'reorder_point' => 10,
                    ]);
                    echo json_encode(['supplierId' => $supplier->id, 'productId' => $product->id]);
                `),
            ) as { supplierId: number; productId: number };

            return {
                ...created,
                supplierName: names.supplier,
                productName: names.product,
            };
        });

        cleanupPurchaseOrderData();
    },

    currentStockOf: async ({}, use) => {
        await use((productId) => Number(runTinker(`echo App\\Models\\Product::query()->whereKey(${Number(productId)})->value('current_stock');`)));
    },

    purchaseOrderNumberFor: async ({}, use) => {
        await use((supplierId) =>
            runTinker(`echo App\\Models\\PurchaseOrder::query()->where('supplier_id', ${Number(supplierId)})->latest('id')->value('number');`),
        );
    },
});
