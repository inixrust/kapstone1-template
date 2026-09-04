# EXERCISE m4.3 — Dashboard: Tren Stok Masuk vs Keluar (Live)

## Tujuan Pembelajaran
- Membuat line chart dengan 2 dataset dan polling (live update).

## Yang Perlu Dibangun
1. `InventoryAnalyticsService::movementTrend(int $days = 30)` — total qty
   masuk (in) vs keluar (out) per hari untuk N hari terakhir (isi 0 untuk hari
   tanpa movement).
2. `ChartWidget` line dengan 2 dataset (Masuk & Keluar), `$pollingInterval = '15s'`.

## Acceptance Criteria
- Test: data agregat per hari benar.
- Manual walkthrough: chart live update tanpa reload.
