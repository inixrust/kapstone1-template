# EXERCISE m4.2 — Audit Trail dengan Activity Log

## Tujuan Pembelajaran
- Mencatat jejak audit otomatis memakai `spatie/laravel-activitylog`.

## Kondisi Codebase Saat Ini
- StockMovement mencatat pergerakan stok, tapi belum ada activity log.

## Yang Perlu Dibangun
1. Trait `LogsActivity` di `StockMovement`:
   - catat causer (user) otomatis.
   - deskripsi khusus untuk tipe `adjustment` (mis. "Penyesuaian stok ...").
2. Relasi `stockActivities()` di `Product` (hasManyThrough Activity via
   StockMovement) untuk melihat activity log per produk.
3. RelationManager "Riwayat Audit" di `ProductResource` (read-only).

## Acceptance Criteria
- Test: activity tercatat otomatis dengan causer & deskripsi benar
  (adjustment punya deskripsi khusus).
- Manual walkthrough: tab riwayat menampilkan log per produk.
