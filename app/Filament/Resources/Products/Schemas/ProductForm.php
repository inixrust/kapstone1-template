<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),
                Select::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('harga_jual')
                    ->label('Harga Jual')
                    ->prefix('Rp')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                // Harga modal = data sensitif. Hidden TOTAL untuk staff
                // (field tidak dirender sama sekali), bukan disamarkan ****.
                TextInput::make('harga_modal')
                    ->label('Harga Modal')
                    ->prefix('Rp')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->visible(fn (): bool => Auth::user()?->can('viewCost', Product::class) ?? false),
                TextInput::make('reorder_point')
                    ->label('Reorder Point')
                    ->helperText('Batas stok minimum sebelum dianggap kritis.')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('unit')
                    ->label('Satuan')
                    ->required()
                    ->default('pcs')
                    ->maxLength(255),
            ]);
    }
}
