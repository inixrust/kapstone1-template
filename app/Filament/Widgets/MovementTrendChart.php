<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Widgets\ChartWidget;

class MovementTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Stok Masuk vs Keluar (30 Hari)';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '15s';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $trend = app(InventoryAnalyticsService::class)->movementTrend(30);

        return [
            'datasets' => [
                [
                    'label' => 'Masuk',
                    'data' => $trend['in'],
                    'borderColor' => '#1F9254',
                    'backgroundColor' => 'rgba(31, 146, 84, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Keluar',
                    'data' => $trend['out'],
                    'borderColor' => '#EA2339',
                    'backgroundColor' => 'rgba(234, 35, 57, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
