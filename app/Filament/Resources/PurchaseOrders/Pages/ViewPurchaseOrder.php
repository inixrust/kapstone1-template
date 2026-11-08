<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

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
            Action::make('approve')
                ->label('Setujui')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->authorize('approve')
                ->visible(fn (PurchaseOrder $record): bool => $this->canBeDecidedByCurrentUser($record))
                ->modalHeading('Setujui purchase order?')
                ->modalSubmitActionLabel('Setujui')
                ->schema([
                    Textarea::make('note')
                        ->label('Catatan')
                        ->rows(3),
                ])
                ->action(function (PurchaseOrder $record, array $data): void {
                    $this->decide(
                        fn () => $record->approve($data['note'] ?? null),
                        successTitle: 'Purchase order disetujui',
                        failureTitle: 'Purchase order gagal disetujui',
                    );
                }),
            Action::make('reject')
                ->label('Tolak')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->authorize('reject')
                ->visible(fn (PurchaseOrder $record): bool => $this->canBeDecidedByCurrentUser($record))
                ->modalHeading('Tolak purchase order?')
                ->modalSubmitActionLabel('Tolak')
                ->schema([
                    Textarea::make('note')
                        ->label('Catatan penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (PurchaseOrder $record, array $data): void {
                    $this->decide(
                        fn () => $record->reject($data['note']),
                        successTitle: 'Purchase order ditolak',
                        failureTitle: 'Purchase order gagal ditolak',
                    );
                }),
            EditAction::make()
                ->visible(fn (PurchaseOrder $record): bool => $record->isDraft()),
        ];
    }

    private function canBeDecidedByCurrentUser(PurchaseOrder $record): bool
    {
        if ($record->status !== PurchaseOrderStatus::Submitted) {
            return false;
        }

        return $record->created_by !== Auth::id();
    }

    private function decide(Closure $decision, string $successTitle, string $failureTitle): void
    {
        try {
            $decision();
        } catch (AuthorizationException $exception) {
            Notification::make()
                ->title($failureTitle)
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($successTitle)
            ->success()
            ->send();
    }
}
