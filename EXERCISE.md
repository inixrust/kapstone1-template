# EXERCISE m3.1 — StockMovement Resource, Observer & Policy

## Tujuan Pembelajaran
- Mencatat pergerakan stok dan meng-update `current_stock` otomatis.
- Menegakkan otorisasi khusus di level Policy/Model (bukan hanya UI).

## Kondisi Codebase Saat Ini
- Product/Category/Supplier + RBAC Shield sudah ada.
- Belum ada pencatatan pergerakan stok.

## Yang Perlu Dibangun
1. Enum `StockMovementType` (`in`, `out`, `adjustment`).
2. Migration `stock_movements`: `product_id` FK, `type`, `qty`, `note`
   (nullable), `user_id` FK (causer), timestamps.
3. Model `StockMovement` (belongsTo Product & User).
4. `StockMovementObserver` — recalc `current_stock` produk otomatis
   (in: +qty, out: -qty, adjustment: set = qty).
5. `StockMovementPolicy`: hanya `manager`/`admin` boleh membuat movement
   bertipe `adjustment`, ditegakkan di **level Policy + Observer** (berlaku
   walau lewat Model langsung, bukan cuma UI). Rujuk guideline security Spatie:
   aksi destruktif method HTTP benar + automated test otorisasi.
6. `StockMovementResource` (form + table).
7. Regenerate permission Shield agar role staff/manager punya izin
   StockMovement.

## Acceptance Criteria
- Test: staff TIDAK bisa membuat `adjustment` (via UI maupun langsung ke Model).
- Test: `current_stock` ter-update benar untuk in/out/adjustment.
- Manual walkthrough input movement.
