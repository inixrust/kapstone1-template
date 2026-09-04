<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\InventoryAnalyticsService;
use Filament\Widgets\ChartWidget;

class CriticalStockChart extends ChartWidget
{
    protected ?string $heading = 'Stok Kritis & Menipis';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $products = app(InventoryAnalyticsService::class)->criticalStockProducts();

        $colors = $products->map(fn (Product $product): string => match ($product->stockStatus()) {
            'critical' => '#EA2339', // merah = kritis
            default => '#F5A623',    // kuning = menipis
        })->all();

        return [
            'datasets' => [
                [
                    'label' => 'Stok saat ini',
                    'data' => $products->pluck('current_stock')->all(),
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
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
