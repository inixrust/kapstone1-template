<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->authorize('submit')
                ->visible(fn (PurchaseOrder $record): bool => $record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Ajukan purchase order?')
                ->modalDescription('Setelah diajukan, item dan harga tidak dapat diubah lagi.')
                ->modalSubmitActionLabel('Ajukan')
                ->action(function (PurchaseOrder $record): void {
                    try {
                        $record->submit();
                    } catch (AuthorizationException $exception) {
                        Notification::make()
                            ->title('Purchase order gagal diajukan')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Purchase order diajukan')
                        ->success()
                        ->send();
                }),
            EditAction::make()
                ->visible(fn (PurchaseOrder $record): bool => $record->isDraft()),
        ];
    }
}
