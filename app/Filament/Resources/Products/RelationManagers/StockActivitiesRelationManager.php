<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'stockActivities';

    protected static ?string $title = 'Riwayat Audit';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Audit Pergerakan Stok')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('causer.name')
                    ->label('Oleh')
                    ->placeholder('Sistem'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
