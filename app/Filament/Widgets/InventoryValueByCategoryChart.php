<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class InventoryValueByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Nilai Inventory per Kategori';

    protected static ?int $sort = 3;

    /**
     * Melibatkan harga modal (data sensitif) → hanya manager/admin.
     */
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && $user->hasAnyRole(['manager', 'admin']);
    }

    protected function getData(): array
    {
        $values = app(InventoryAnalyticsService::class)->inventoryValueByCategory();

        return [
            'datasets' => [
                [
                    'label' => 'Nilai Inventory (Rp)',
                    'data' => $values->values()->all(),
                    'backgroundColor' => [
                        '#143564', '#5CAAD5', '#1F9254', '#EA2339', '#F5A623',
                        '#8E44AD', '#16A085', '#E67E22', '#2C3E50', '#C0392B',
                    ],
                ],
            ],
            'labels' => $values->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
