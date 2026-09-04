# EXERCISE m5.2 — Testing Lengkap dengan Pest

## Tujuan Pembelajaran
- Membuktikan otorisasi lewat automated test yang menyeluruh (pemenuhan
  langsung guideline security Spatie).

## Yang Perlu Dibangun
1. Authorization matrix test: staff/manager/admin × semua Resource
   (Product, Category, Supplier, StockMovement, Role) untuk ability
   view/create/update/delete/forceDelete.
2. Pastikan test masking, audit trail, dan seluruh widget dashboard sudah
   tercakup (dari modul sebelumnya).

## Acceptance Criteria
- `php artisan test` hijau (seluruh suite).
