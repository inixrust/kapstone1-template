<?php

namespace App\Filament\Widgets;

use App\Services\InventoryAnalyticsService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class InventoryStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $analytics = app(InventoryAnalyticsService::class);

        $criticalCount = $analytics->criticalStockProductCount();

        $stats = [
            Stat::make('Total Produk', Number::format($analytics->totalProducts()))
                ->description('Jumlah SKU terdaftar')
                ->color('info'),
            Stat::make('Total Unit Stok', Number::format($analytics->totalStockUnits()))
                ->description('Akumulasi seluruh current stock')
                ->color('primary'),
            Stat::make('Produk Stok Kritis', Number::format($criticalCount))
                ->description('Stok ≤ reorder point')
                ->color($criticalCount > 0 ? 'danger' : 'success'),
        ];

        // Nilai inventory memakai harga modal → hanya manager/admin.
        $user = Filament::auth()->user();
        if ($user !== null && $user->hasAnyRole(['manager', 'admin'])) {
            $stats[] = Stat::make(
                'Total Nilai Inventory',
                'Rp '.Number::format($analytics->totalInventoryValue())
            )
                ->description('Berdasarkan harga modal')
                ->color('success');
        }

        return $stats;
    }
}
