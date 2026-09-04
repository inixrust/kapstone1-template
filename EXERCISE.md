# EXERCISE m1.2 — Resource Supplier & Relasi

## Tujuan Pembelajaran
- Membuat relasi antar-entitas dan RelationManager Filament.

## Kondisi Codebase Saat Ini
- Sudah ada Product & Category beserta resource-nya.
- Belum ada entitas Supplier.

## Yang Perlu Dibangun
1. Migration `suppliers`: `name`, `email` (nullable), `phone` (nullable),
   `address` (nullable), timestamps.
2. Migration menambah `supplier_id` (nullable FK) ke `products`.
3. Model `Supplier` (hasMany Product) & update `Product` (belongsTo Supplier).
4. `SupplierResource` (form + table).
5. `ProductsRelationManager` di `SupplierResource` — menampilkan produk milik
   supplier tersebut.

## Acceptance Criteria
- Pest feature test CRUD Supplier lulus.
- RelationManager menampilkan produk supplier dengan benar (teruji).
