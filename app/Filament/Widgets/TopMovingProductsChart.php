<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class TopMovingProductsChart extends ChartWidget
{
    protected ?string $heading = 'Top 10 Produk Fast-Moving (30 Hari)';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && $user->hasAnyRole(['manager', 'admin']);
    }

    protected function getData(): array
    {
        $data = app(InventoryAnalyticsService::class)->topMovingProducts(30, 10);

        return [
            'datasets' => [
                [
                    'label' => 'Total keluar (unit)',
                    'data' => $data->values()->all(),
                    'backgroundColor' => '#5CAAD5',
                    'borderColor' => '#5CAAD5',
                ],
            ],
            'labels' => $data->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Horizontal bar via indexAxis 'y'.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
        ];
    }
}
