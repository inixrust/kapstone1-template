<?php

namespace App\Observers;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderObserver
{
    /**
     * PO selalu lahir sebagai draft dengan nomor urut per bulan. Unique index
     * pada `number` menjadi penjaga terakhir bila dua draft dibuat bersamaan.
     */
    public function creating(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->status = PurchaseOrderStatus::Draft;

        if (Auth::check() && $purchaseOrder->created_by === null) {
            $purchaseOrder->created_by = Auth::id();
        }

        if (blank($purchaseOrder->number)) {
            $purchaseOrder->number = $this->nextNumber();
        }
    }

    /**
     * Supplier dan nomor tetap setelah draft dibuat; status hanya boleh bergerak
     * sesuai state machine. Berlaku untuk semua user, termasuk admin.
     */
    public function updating(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->isDirty('supplier_id')) {
            throw new AuthorizationException('Supplier purchase order tidak dapat diganti.');
        }

        if ($purchaseOrder->isDirty('number')) {
            throw new AuthorizationException('Nomor purchase order tidak dapat diubah.');
        }

        if (! $purchaseOrder->isDirty('status')) {
            return;
        }

        $originalStatus = PurchaseOrderStatus::from($purchaseOrder->getRawOriginal('status'));

        if (! $originalStatus->canTransitionTo($purchaseOrder->status)) {
            throw new AuthorizationException(
                "Status purchase order tidak dapat berubah dari {$originalStatus->getLabel()} ke {$purchaseOrder->status->getLabel()}."
            );
        }
    }

    public function deleting(PurchaseOrder $purchaseOrder): void
    {
        if (! $purchaseOrder->isDraft()) {
            throw new AuthorizationException('Hanya purchase order berstatus draft yang dapat dihapus.');
        }
    }

    private function nextNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ym').'-';

        $lastNumber = PurchaseOrder::query()
            ->where('number', 'like', "{$prefix}%")
            ->orderByDesc('number')
            ->lockForUpdate()
            ->value('number');

        $nextSequence = $lastNumber === null
            ? 1
            : (int) substr($lastNumber, -4) + 1;

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
