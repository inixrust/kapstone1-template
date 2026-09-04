<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label('Tipe Pergerakan')
                    ->options(self::availableTypes())
                    ->required()
                    ->native(false)
                    ->helperText('Penyesuaian (adjustment) hanya untuk manager/admin.'),
                TextInput::make('qty')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Untuk penyesuaian, ini adalah nilai stok akhir yang benar.'),
                Textarea::make('note')
                    ->label('Catatan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Opsi tipe pergerakan sesuai izin user: staff hanya in/out,
     * manager/admin termasuk adjustment.
     *
     * @return array<string, string>
     */
    private static function availableTypes(): array
    {
        $types = [
            StockMovementType::In->value => StockMovementType::In->getLabel(),
            StockMovementType::Out->value => StockMovementType::Out->getLabel(),
        ];

        if (Auth::check() && Auth::user()->can('adjust', StockMovement::class)) {
            $types[StockMovementType::Adjustment->value] = StockMovementType::Adjustment->getLabel();
        }

        return $types;
    }
}
