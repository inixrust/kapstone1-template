<?php

namespace App\Observers;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class StockMovementObserver
{
    /**
     * Tegakkan otorisasi adjustment di level Model (berlaku untuk SEMUA jalur
     * penyimpanan, bukan hanya UI). Jika ada user login yang tidak berwenang,
     * pembuatan adjustment ditolak. Konteks CLI/seeder (tanpa user login)
     * dianggap tepercaya.
     */
    public function creating(StockMovement $movement): void
    {
        if (Auth::check() && $movement->user_id === null) {
            $movement->user_id = Auth::id();
        }

        if ($movement->type === StockMovementType::Adjustment && Auth::check()) {
            if (Auth::user()->cannot('adjust', StockMovement::class)) {
                throw new AuthorizationException(
                    'Hanya manager/admin yang boleh melakukan penyesuaian (adjustment) stok.'
                );
            }
        }
    }

    /**
     * Movement yang merujuk PO tidak boleh diubah atau dihapus oleh siapa pun
     * (termasuk admin), agar stok dan jejak penerimaan PO tetap utuh.
     */
    public function updating(StockMovement $movement): void
    {
        if ($movement->isFromPurchaseOrder()) {
            throw new AuthorizationException('Pergerakan stok dari penerimaan purchase order tidak dapat diubah.');
        }
    }

    public function deleting(StockMovement $movement): void
    {
        if ($movement->isFromPurchaseOrder()) {
            throw new AuthorizationException('Pergerakan stok dari penerimaan purchase order tidak dapat dihapus.');
        }
    }

    public function created(StockMovement $movement): void
    {
        $this->recalculateStock($movement->product_id);
    }

    public function updated(StockMovement $movement): void
    {
        $this->recalculateStock($movement->product_id);

        if ($movement->wasChanged('product_id')) {
            $original = $movement->getOriginal('product_id');
            if ($original !== null && $original !== $movement->product_id) {
                $this->recalculateStock($original);
            }
        }
    }

    public function deleted(StockMovement $movement): void
    {
        $this->recalculateStock($movement->product_id);
    }

    /**
     * Hitung ulang current_stock dengan me-replay seluruh movement produk
     * secara urut. Cara ini selalu konsisten apa pun operasinya
     * (create/update/delete) dan tidak tergantung urutan pemanggilan.
     */
    private function recalculateStock(?int $productId): void
    {
        if ($productId === null) {
            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $stock = 0;

        StockMovement::query()
            ->where('product_id', $productId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['type', 'qty'])
            ->each(function (StockMovement $movement) use (&$stock): void {
                $stock = match ($movement->type) {
                    StockMovementType::In => $stock + $movement->qty,
                    StockMovementType::Out => $stock - $movement->qty,
                    StockMovementType::Adjustment => $movement->qty,
                };
            });

        $product->current_stock = $stock;
        $product->saveQuietly();
    }
}
