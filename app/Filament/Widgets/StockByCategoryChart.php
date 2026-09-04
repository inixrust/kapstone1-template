<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Widgets\ChartWidget;

class StockByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Stok per Kategori';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $stock = app(InventoryAnalyticsService::class)->stockByCategory();

        return [
            'datasets' => [
                [
                    'label' => 'Total Stok (unit)',
                    'data' => $stock->values()->all(),
                    'backgroundColor' => '#143564',
                    'borderColor' => '#143564',
                ],
            ],
            'labels' => $stock->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
