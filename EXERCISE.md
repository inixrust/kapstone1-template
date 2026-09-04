# EXERCISE m5.3 — Dashboard Analytics Lanjutan

## Tujuan Pembelajaran
- Chart lanjutan: horizontal bar & mixed (bar+line) dalam satu `getData()`.

## Yang Perlu Dibangun
1. `InventoryAnalyticsService::topMovingProducts(int $days = 30, int $limit = 10)`
   — produk paling banyak keluar (fast-moving).
2. `InventoryAnalyticsService::abcAnalysis()` — klasifikasi Pareto berbasis
   nilai inventory (A ≤80%, B ≤95%, C sisanya) dengan cumulative %.
3. `ChartWidget` Top 10 fast-moving (horizontal bar).
4. `ChartWidget` ABC Analysis (mixed bar + line dalam satu `getData()`).
5. Keduanya `canView()` manager/admin only (melibatkan/berdekatan dengan data
   nilai/harga modal).

## Acceptance Criteria
- Test data akurat terhadap data uji spesifik.
- Manual walkthrough kedua chart.
