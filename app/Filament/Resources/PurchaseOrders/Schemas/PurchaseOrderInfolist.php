<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase Order')
                    ->schema([
                        TextEntry::make('number')
                            ->label('Nomor PO'),
                        TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('creator.name')
                            ->label('Dibuat oleh')
                            ->placeholder('Sistem'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),
                        // Total dihitung dari harga modal: hidden total untuk staff.
                        TextEntry::make('total')
                            ->label('Total')
                            ->state(fn (PurchaseOrder $record): float => $record->total())
                            ->money('IDR', locale: 'id')
                            ->visible(fn (): bool => self::canViewCost()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                RepeatableEntry::make('items')
                    ->label('Item')
                    ->schema([
                        TextEntry::make('product.name')
                            ->label('Produk'),
                        TextEntry::make('qty')
                            ->label('Jumlah')
                            ->numeric(),
                        TextEntry::make('unit_price')
                            ->label('Harga per Item')
                            ->money('IDR', locale: 'id')
                            ->visible(fn (): bool => self::canViewCost()),
                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->state(fn (PurchaseOrderItem $record): float => $record->subtotal())
                            ->money('IDR', locale: 'id')
                            ->visible(fn (): bool => self::canViewCost()),
                    ])
                    ->columns(4)
                    ->placeholder('Belum ada item.')
                    ->columnSpanFull(),
            ]);
    }

    private static function canViewCost(): bool
    {
        return Auth::user()?->can('viewCost', Product::class) ?? false;
    }
}
