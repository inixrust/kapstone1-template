---
title: 'Filter "Status stok" pada tabel Produk'
type: 'feature'
created: '2026-11-08'
status: 'done'
route: 'oneshot'
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Tabel Produk di panel admin hanya bisa difilter per kategori; pengguna tidak bisa menyaring produk berdasarkan status stok (kritis / rendah / aman) yang sudah ditampilkan lewat warna badge kolom Stok.

**Approach:** Tambahkan `SelectFilter` "Status stok" dengan opsi Kritis / Rendah / Aman yang memfilter lewat query scope di model `Product`. Ambang batas (reorder point dan pengali 1,5) didefinisikan sekali di model dan dipakai bersama oleh `stockStatus()` dan scope, sehingga tidak ada duplikasi. Disertai test Pest.

</frozen-after-approval>

## Implementation Notes

- Asumsi: label opsi mengikuti permintaan ("Kritis", "Rendah", "Aman"); nilai filter memakai kunci yang sudah dikembalikan `stockStatus()` (`critical`, `low`, `safe`).
- Asumsi: filter terlihat untuk semua role yang boleh melihat daftar produk (status stok bukan data harga modal).
- `InventoryAnalyticsService` punya ekspresi ambangnya sendiri (tanpa `ROUND`); di luar cakupan, tidak diubah.
- Berkas: `app/Models/Product.php` (konstanta `LowStockMultiplier`, scope `#[Scope] withStockStatus()`), `app/Filament/Resources/Products/Tables/ProductsTable.php` (`SelectFilter` `stock_status`), `tests/Feature/ProductResourceTest.php`.
- Pengali disisipkan sebagai literal SQL, bukan binding: binding string membuat MySQL menghitung DOUBLE dan ROUND bisa "half to even" (10,5 → 10), berbeda dari `round()` PHP. Test kasus reorder_point 7 / stok 11 menjaga hal ini.
- Commit tidak dibuat: alur dry run menetapkan fasilitator yang meng-commit.

## Review Triage Log

- Kunci status masih string di banyak tempat (usul enum) — low; nyata tetapi refaktor di luar cakupan (ambang sudah tunggal) → defer.
- Service analitik tidak memakai ROUND/konstanta — medium; sudah ada sebelumnya → defer.
- Scope tanpa arm `default` → `UnhandledMatchError` — false; gagal keras untuk nilai tak valid itu benar, satu-satunya pemanggil UI sudah memvalidasi nilai.
- Kolom tidak dikualifikasi di scope — false; query tabel Produk tidak memakai join (sort/search relasi Filament memakai subquery/whereHas).
- Test batas hanya cek inklusi — low → patch: test kini memastikan produk cocok dengan tepat satu status, ditambah kasus reorder_point 0.
- Guard nilai filter tak dikenal belum dites — low → patch: test `bogus` ditambahkan. Test manager/harga modal ditolak: filter bukan fitur otorisasi baru; penyembunyian harga modal sudah dicakup `CostMaskingTest`.
- Docblock `stockStatus()` menulis "1.5x" — low → patch: merujuk konstanta.
- Relation manager Supplier tidak punya filter — false untuk cakupan ini; permintaan hanya tabel Produk.
