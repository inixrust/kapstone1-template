<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Admin memegang semua permission Shield, jadi lolos setiap cek `Action:PurchaseOrder`;
 * aturan status dan larangan pembuat memutuskan PO sendiri ditegakkan juga di
 * PurchaseOrderObserver dan method transisi PurchaseOrder.
 */
class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchaseOrder');
    }

    public function view(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('View:PurchaseOrder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchaseOrder');
    }

    public function update(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        if (! $purchaseOrder->isDraft()) {
            return false;
        }

        return $authUser->can('Update:PurchaseOrder');
    }

    /**
     * Mengajukan draft = mengubah PO, jadi memakai permission Update.
     */
    public function submit(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        if (! $purchaseOrder->isDraft()) {
            return false;
        }

        return $authUser->can('Update:PurchaseOrder');
    }

    /**
     * Hanya admin yang boleh memutuskan PO. Larangan pembuat memutuskan PO
     * sendiri ditegakkan di PurchaseOrder::approve()/reject(), bukan di sini.
     */
    public function approve(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $this->canDecide($authUser, $purchaseOrder);
    }

    public function reject(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $this->canDecide($authUser, $purchaseOrder);
    }

    private function canDecide(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Submitted) {
            return false;
        }

        return $authUser->hasRole('admin');
    }

    public function delete(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        if (! $purchaseOrder->isDraft()) {
            return false;
        }

        return $authUser->can('Delete:PurchaseOrder');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchaseOrder');
    }

    public function restore(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('Restore:PurchaseOrder');
    }

    public function forceDelete(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('ForceDelete:PurchaseOrder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PurchaseOrder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PurchaseOrder');
    }

    public function replicate(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool
    {
        return $authUser->can('Replicate:PurchaseOrder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PurchaseOrder');
    }
}
