# EXERCISE m3.2 — Dashboard Widget Dasar

## Tujuan Pembelajaran
- Membuat dashboard analytics dengan widget Filament native (Chart.js).
- Memusatkan logika agregasi di `InventoryAnalyticsService`.

## Kondisi Codebase Saat Ini
- Master data + stock movement + RBAC lengkap.
- Dashboard masih default (Account + Filament info).

## Yang Perlu Dibangun
1. `app/Services/InventoryAnalyticsService.php` dengan method publik:
   - `stockByCategory()` — total stok per kategori.
   - `inventoryValueByCategory()` — nilai inventory (stok × harga modal) per
     kategori.
   - KPI: total produk, total unit stok, jumlah produk stok kritis, total nilai
     inventory.
   (Widget HANYA memanggil service, JANGAN query agregasi di dalam widget.)
2. `StatsOverviewWidget` — 4 KPI.
3. `ChartWidget` bar: stok per kategori.
4. `ChartWidget` doughnut: nilai inventory per kategori — `canView()` hanya
   manager/admin (melibatkan harga modal).

## Acceptance Criteria
- Test `canView()` benar (doughnut tersembunyi untuk staff).
- Test data agregat benar.
- Manual walkthrough dashboard per role.
