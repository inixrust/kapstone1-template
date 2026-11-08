<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->helperText('Supplier tidak dapat diganti setelah draft dibuat.')
                    ->afterStateUpdated(fn (int|string|null $state, Set $set) => $set('items', self::criticalItemsFor($state))),
                Repeater::make('items')
                    ->label('Item')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->options(fn (Get $get): array => Product::query()
                                ->where('supplier_id', $get('../../supplier_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->distinct()
                            ->live()
                            ->afterStateUpdated(fn (int|string|null $state, Set $set) => $set('unit_price', Product::query()->whereKey($state)->value('harga_modal')))
                            ->columnSpan(2),
                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1),
                        // Harga per item berasal dari harga modal: hidden total untuk staff.
                        TextInput::make('unit_price')
                            ->label('Harga per Item')
                            ->prefix('Rp')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (): bool => Auth::user()?->can('viewCost', Product::class) ?? false),
                    ])
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Tambah item')
                    ->helperText('Item awal diisi produk kritis supplier; produk lain milik supplier yang sama boleh ditambahkan.')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Item awal draft: produk kritis milik supplier, harga = harga modal produk.
     *
     * @return array<string, array{product_id: int, qty: null, unit_price: string}>
     */
    private static function criticalItemsFor(int|string|null $supplierId): array
    {
        if (blank($supplierId)) {
            return [];
        }

        return Product::query()
            ->where('supplier_id', $supplierId)
            ->withStockStatus('critical')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                (string) Str::uuid() => [
                    'product_id' => $product->id,
                    'qty' => null,
                    'unit_price' => $product->harga_modal,
                ],
            ])
            ->all();
    }
}
