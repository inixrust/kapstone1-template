# EXERCISE m1.1 — Resource Product & Category

## Tujuan Pembelajaran
- Membuat migration, model, relasi, dan Filament Resource untuk master data.
- Memakai Form Builder & Table Builder Filament.

## Kondisi Codebase Saat Ini
- Panel Filament ber-brand aktif.
- Belum ada model/tabel domain (Product, Category).

## Yang Perlu Dibangun
1. Migration `categories`: `name`, timestamps.
2. Migration `products`: `sku` (unique), `name`, `category_id` (FK),
   `harga_jual`, `harga_modal`, `reorder_point`, `unit`,
   `current_stock` (default 0), timestamps.
3. Model `Category` (hasMany Product) & `Product` (belongsTo Category) dengan
   `$fillable`/`$casts` yang benar.
4. Factory untuk keduanya.
5. `CategoryResource` & `ProductResource` (form + table schema).

## Acceptance Criteria
- Pest feature test CRUD Category & Product lulus.
- Manual walkthrough CRUD di `/admin` berhasil.
