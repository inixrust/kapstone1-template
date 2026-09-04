<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Widgets\ChartWidget;

class DeadStockChart extends ChartWidget
{
    protected ?string $heading = 'Dead Stock (tanpa pergerakan 60 hari)';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $products = app(InventoryAnalyticsService::class)->deadStockProducts(60);

        return [
            'datasets' => [
                [
                    'label' => 'Stok mengendap',
                    'data' => $products->pluck('current_stock')->all(),
                    'backgroundColor' => '#2C3E50',
                    'borderColor' => '#2C3E50',
                ],
            ],
            'labels' => $products->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
