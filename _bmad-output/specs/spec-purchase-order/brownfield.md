# Brownfield: titik sentuh dan jebakan

Kode yang sudah ada dan memengaruhi keputusan epik ini.

## Titik sentuh

| Area | Berkas | Relevansi |
|---|---|---|
| Status kritis | `app/Models/Product.php` — `stockStatus()`, scope `withStockStatus('critical')` | Sumber daftar awal item CAP-1; jangan membuat definisi kritis kedua. |
| Supplier ↔ produk | `Product::supplier()` / `Supplier::products()` (`products.supplier_id`, nullable) | Produk tanpa supplier tidak pernah muncul di PO. |
| Harga modal | `ProductPolicy::viewCost()` (manager/admin); pola `->visible(fn () => Auth::user()?->can('viewCost', Product::class) ?? false)` di `ProductForm`/`ProductsTable` | Setiap field/kolom/entri harga PO memakai gate yang sama. |
| Stok | `StockMovement` + `StockMovementObserver` (replay semua movement per produk) | CAP-3 membuat movement `StockMovementType::In` lewat `StockMovement::create()` (bukan query builder, bukan `saveQuietly`). Staff boleh membuat IN — tidak ada perubahan Observer untuk adjustment. |
| Audit | `spatie/laravel-activitylog` — `LogsActivity` + `getActivitylogOptions()` seperti `StockMovement` (log name sendiri, `logOnlyDirty`) | Status PO dicatat dengan causer = user login. |
| Otorisasi | `database/seeders/RoleSeeder.php` (`DOMAIN_RESOURCES`, `MANAGER_ACTIONS`, `STAFF_*`), `ShieldSeeder` meregenerasi permission `Action:Resource` | Tambah `PurchaseOrder`; staff cukup `ViewAny`/`View` + ability terima. |
| Test | `tests/Pest.php` (`userWithRole()`, `adminUser()`), `AuthorizationMatrixTest`, `CostMaskingTest`, `AuditTrailTest`, `StockMovementTest` | Test baru di `tests/Feature/`; buktikan akses dengan `userWithRole('staff'|'manager')`. |
| E2E | `tests/e2e/auth.setup.ts` (sesi per role), `tests/support/fixtures/` (fixture + cleanup), `tests/README.md` | E2E memakai database dev: data yang dibuat harus dibersihkan fixture; satu worker; throttle login 5/menit. |
| Navigasi | `StockMovementResource` di grup `Transaksi` | Resource PO di grup yang sama. |

## Jebakan

- **Admin melewati semua Policy.** `config/filament-shield.php` `super_admin.name = admin`, `intercept_gate = before`: `$admin->can(...)` selalu true, termasuk ability kustom. Aturan "pembuat tidak boleh memutuskan PO sendiri" dan aturan transisi status harus ditegakkan di jalur domain (model/aksi, seperti adjustment ditegakkan di `StockMovementObserver`) dan diuji dengan admin pembuat.
- **Movement bisa diubah/dihapus.** `StockMovementPolicy` memberi staff `Update:StockMovement` dan manager `Delete:StockMovement`; tanpa penjagaan, movement hasil PO bisa diedit/dihapus dan stok di-replay ulang, memutus jejak ke PO. Movement yang merujuk PO harus ditolak untuk update/delete (Policy + tabel/aksi edit), dan admin bypass ikut diperhitungkan.
- **`current_stock` tidak fillable** dan hanya ditulis Observer; jangan menulisnya saat penerimaan.
- **Skema `stock_movements` tidak boleh diubah lewat migrasi lama**; rujukan ke PO memerlukan migrasi baru.
- **Nomor urut per bulan** butuh penjagaan konkurensi (unik di DB + kunci saat menghitung urutan) agar dua draft simultan tidak mendapat nomor sama.
- **Perintah runtime** lewat `docker compose exec -T -u sail laravel.test …`; jangan pernah tanpa `-u sail`.
