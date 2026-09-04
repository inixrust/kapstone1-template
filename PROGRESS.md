# PROGRESS.md — Ringkasan Capstone

Status: **SELESAI**. Seluruh 16 fitur (Modul 0–5) diimplementasikan berurutan
dengan pasangan branch `starter/` dan `solution/`. `main` = hasil merge
`solution/m5.4-capstone`.

## Ringkasan per Modul

| Fitur | Judul | Status | Bukti |
|-------|-------|--------|-------|
| m0.1 | Backend Bootstrap | ✅ | Laravel 13 + Filament 5 + Sail/MySQL + Shield + Activitylog + Pest + Boost |
| m0.2 | Brand & Theme | ✅ | Navy `#143564`, logo, footer brand |
| m1.1 | Product & Category | ✅ | Migration, model, resource, test CRUD |
| m1.2 | Supplier & Relasi | ✅ | RelationManager produk per supplier |
| m2.1 | Auth & Login | ✅ | 3 akun demo via Seeder, test login |
| m2.2 | Role & Shield | ✅ | 3 role berjenjang, 48+ permission, policy |
| m3.1 | Stock Movement | ✅ | Observer recalc, adjustment ditegakkan Policy+Observer |
| m3.2 | Dashboard Dasar | ✅ | StatsOverview + 2 ChartWidget |
| m4.1 | Masking Harga Modal | ✅ | Hidden total untuk staff |
| m4.2 | Audit Trail | ✅ | Activity log + tab Riwayat Audit per produk |
| m4.3 | Tren Stok (Live) | ✅ | Line 2 dataset, polling 15s |
| m4.4 | Kritis & Dead Stock | ✅ | Bar warna kondisional + dead stock |
| m5.1 | Hardening | ✅ | Anti mass-assignment, rate limit, CSRF |
| m5.2 | Testing Lengkap | ✅ | Authorization matrix menyeluruh |
| m5.3 | Dashboard Lanjutan | ✅ | Top moving + ABC (bar+line) |
| m5.4 | Capstone | ✅ | Data demo, README, brand polish |

## Dashboard: 7 ChartWidget + 1 StatsOverview (Chart.js native)

StockByCategory, InventoryValueByCategory, MovementTrend (live), CriticalStock,
DeadStock, TopMovingProducts, AbcAnalysis + InventoryStatsOverview.
Semua agregasi di `InventoryAnalyticsService`.

## Yang Selesai 100% Sesuai Spesifikasi

- Arsitektur Filament (tanpa REST API/Swagger), CRUD via Resource, chart via
  ChartWidget native tanpa package tambahan.
- RBAC 3 role, masking harga modal, audit trail, penegakan adjustment di level
  Model (bukan hanya UI).
- Guideline Spatie + skill Laravel Boost aktif (lihat `DECISIONS.md`).
- Database MySQL di Docker Desktop (Sail), bukan SQLite.
- Semua acceptance criteria diverifikasi automated test (suite hijau).

## Asumsi / Penyesuaian Mandiri (detail di DECISIONS.md)

- Filament Shield v4 memakai `shield:install`/`shield:generate` (bukan
  `shield:setup` gaya lama); keduanya jalan non-interaktif.
- Super admin = role `admin` (config Shield diubah), `panel_user` dimatikan.
- `adjustment` diinterpretasikan sebagai **set nilai stok akhir** (koreksi),
  dan `current_stock` dihitung ulang dengan replay movement.
- Warna `success #1F9254` dipilih (di luar brand asset resmi).
- Top-moving & ABC chart dibatasi manager/admin (berdekatan dengan data nilai).

## Keterbatasan

- Single warehouse (multi-lokasi di luar cakupan).
- Fitur AI/rekomendasi belum diimplementasikan — hanya arsitektur disiapkan
  (lihat README "Next Steps").
- Down-method migration tetap ditulis (menyimpang dari 1 poin guideline Spatie)
  demi kemudahan rollback/testing.
