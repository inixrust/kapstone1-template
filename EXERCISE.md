# EXERCISE m4.4 — Dashboard: Stok Kritis & Dead Stock

## Tujuan Pembelajaran
- Membuat chart dengan warna kondisional dan query analitik (dead stock).

## Yang Perlu Dibangun
1. `InventoryAnalyticsService::criticalStockProducts()` — produk dengan stok
   kritis/menipis (stok ≤ 1.5× reorder point), diurutkan.
2. `InventoryAnalyticsService::deadStockProducts(int $days = 60)` — produk tanpa
   pergerakan stok selama N hari terakhir.
3. `ChartWidget` bar stok kritis (warna kondisional merah = kritis / kuning =
   menipis).
4. `ChartWidget` bar dead stock.

## Acceptance Criteria
- Test: query stok kritis & dead stock benar (dengan data uji spesifik).
- Manual walkthrough kedua chart.
