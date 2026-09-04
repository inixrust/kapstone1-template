<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Pusat SEMUA logika agregasi data untuk dashboard.
 *
 * Setiap method bersifat publik, deskriptif, dan read-only sehingga nanti bisa
 * dipakai ulang sebagai Tool untuk Laravel AI SDK tanpa duplikasi query.
 * Widget Filament HARUS memanggil method di sini untuk `getData()`-nya.
 */
class InventoryAnalyticsService
{
    /**
     * Total unit stok saat ini per kategori.
     *
     * @return Collection<string, int> [nama kategori => total stok]
     */
    public function stockByCategory(): Collection
    {
        return Category::query()
            ->withSum('products as stock_sum', 'current_stock')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->name => (int) ($category->stock_sum ?? 0),
            ]);
    }

    /**
     * Nilai inventory (stok × harga modal) per kategori.
     *
     * @return Collection<string, float> [nama kategori => total nilai]
     */
    public function inventoryValueByCategory(): Collection
    {
        return Product::query()
            ->selectRaw('categories.name as category_name')
            ->selectRaw('SUM(products.current_stock * products.harga_modal) as value')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->groupBy('categories.name')
            ->orderBy('categories.name')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                $row->category_name => (float) $row->value,
            ]);
    }

    /**
     * Tren total qty masuk (in) vs keluar (out) per hari untuk N hari terakhir.
     * Hari tanpa movement diisi 0 sehingga sumbu waktu selalu lengkap.
     *
     * @return array{labels: list<string>, in: list<int>, out: list<int>}
     */
    public function movementTrend(int $days = 30): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);

        $rows = StockMovement::query()
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(CASE WHEN type = ? THEN qty ELSE 0 END) as total_in', [StockMovementType::In->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN qty ELSE 0 END) as total_out', [StockMovementType::Out->value])
            ->where('created_at', '>=', $start->startOfDay())
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $in = [];
        $out = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $key = $date->format('Y-m-d');

            $labels[] = $date->format('d M');
            $in[] = (int) ($rows[$key]->total_in ?? 0);
            $out[] = (int) ($rows[$key]->total_out ?? 0);
        }

        return ['labels' => $labels, 'in' => $in, 'out' => $out];
    }

    public function totalProducts(): int
    {
        return Product::query()->count();
    }

    public function totalStockUnits(): int
    {
        return (int) Product::query()->sum('current_stock');
    }

    /**
     * Jumlah produk yang stoknya di bawah/di titik reorder (kritis).
     */
    public function criticalStockProductCount(): int
    {
        return Product::query()
            ->whereColumn('current_stock', '<=', 'reorder_point')
            ->count();
    }

    /**
     * Total nilai seluruh inventory berdasarkan harga modal.
     */
    public function totalInventoryValue(): float
    {
        return (float) Product::query()
            ->selectRaw('SUM(current_stock * harga_modal) as value')
            ->value('value') ?? 0.0;
    }
}
