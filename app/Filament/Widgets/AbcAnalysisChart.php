<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class AbcAnalysisChart extends ChartWidget
{
    protected ?string $heading = 'Analisis ABC (Pareto Nilai Inventory)';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && $user->hasAnyRole(['manager', 'admin']);
    }

    protected function getData(): array
    {
        $abc = app(InventoryAnalyticsService::class)->abcAnalysis();

        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Nilai Inventory (Rp)',
                    'data' => $abc->pluck('value')->all(),
                    'backgroundColor' => '#143564',
                    'borderColor' => '#143564',
                    'yAxisID' => 'y',
                    'order' => 2,
                ],
                [
                    'type' => 'line',
                    'label' => 'Kumulatif (%)',
                    'data' => $abc->pluck('cumulative_percentage')->all(),
                    'borderColor' => '#EA2339',
                    'backgroundColor' => '#EA2339',
                    'yAxisID' => 'y1',
                    'order' => 1,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $abc->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Dua sumbu Y: kiri untuk nilai (bar), kanan 0–100% untuk kurva kumulatif.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'beginAtZero' => true,
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'beginAtZero' => true,
                    'max' => 100,
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
        ];
    }
}
