<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /**
     * Lapisan kedua di samping Policy `update`: halaman edit PO selain draft ditutup di sini juga.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->getRecord();

        abort_unless($purchaseOrder->isDraft(), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
