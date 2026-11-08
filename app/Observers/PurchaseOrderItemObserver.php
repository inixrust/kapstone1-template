<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Item hanya bisa diubah selama PO masih draft dan hanya berisi produk milik
 * supplier PO. Ditegakkan di level model agar berlaku juga untuk admin.
 */
class PurchaseOrderItemObserver
{
    public function creating(PurchaseOrderItem $item): void
    {
        $purchaseOrder = $this->ensureDraftPurchaseOrder($item);

        $this->ensureProductBelongsToSupplier($item, $purchaseOrder);
    }

    public function updating(PurchaseOrderItem $item): void
    {
        $purchaseOrder = $this->ensureDraftPurchaseOrder($item);

        if ($item->isDirty('purchase_order_id')) {
            throw new AuthorizationException('Item tidak dapat dipindahkan ke purchase order lain.');
        }

        if ($item->isDirty('product_id')) {
            $this->ensureProductBelongsToSupplier($item, $purchaseOrder);
        }
    }

    public function deleting(PurchaseOrderItem $item): void
    {
        $this->ensureDraftPurchaseOrder($item);
    }

    private function ensureDraftPurchaseOrder(PurchaseOrderItem $item): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::query()->find($item->getRawOriginal('purchase_order_id') ?? $item->purchase_order_id);

        if ($purchaseOrder === null) {
            throw new AuthorizationException('Purchase order untuk item ini tidak ditemukan.');
        }

        if (! $purchaseOrder->isDraft()) {
            throw new AuthorizationException('Item purchase order hanya dapat diubah selama berstatus draft.');
        }

        return $purchaseOrder;
    }

    private function ensureProductBelongsToSupplier(PurchaseOrderItem $item, PurchaseOrder $purchaseOrder): void
    {
        $productSupplierId = Product::query()
            ->whereKey($item->product_id)
            ->value('supplier_id');

        if ($productSupplierId === null || (int) $productSupplierId !== (int) $purchaseOrder->supplier_id) {
            throw new AuthorizationException('Produk bukan milik supplier purchase order ini.');
        }
    }
}
