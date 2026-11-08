- source_spec: `_bmad-output/implementation-artifacts/spec-filter-status-stok-produk.md`
  summary: Selaraskan `InventoryAnalyticsService::criticalStockProducts()` dengan scope `Product::withStockStatus()` / konstanta `LowStockMultiplier`.
  evidence: Service memakai `current_stock <= reorder_point * 1.5` tanpa ROUND; untuk reorder_point 7 dan stok 11, badge/filter menyatakan "Rendah" tetapi chart stok kritis tidak menampilkannya. Sudah ada sebelum perubahan ini.
- source_spec: `_bmad-output/implementation-artifacts/spec-filter-status-stok-produk.md`
  summary: Pertimbangkan enum `StockStatus` (label + warna) agar kunci `critical`/`low`/`safe` tidak tersebar sebagai string di model, tabel Produk, dan relation manager Supplier.
  evidence: String status muncul di `Product::stockStatus()`, scope, `ProductsTable` (opsi filter + warna badge), dan `ProductsRelationManager`; salah ketik tidak terdeteksi.
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/1-buat-draft-po-dari-produk-kritis.md`
  summary: Tangani penghapusan produk/supplier yang sudah dipakai PO (FK `restrictOnDelete`) dengan pesan ramah, bukan error 500.
  evidence: `DeleteAction` di resource Produk/Supplier memanggil `delete()` tanpa cek; FK restrict melempar `QueryException`. Belum diuji; muncul begitu ada PO di data dev.
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/1-buat-draft-po-dari-produk-kritis.md`
  summary: Samakan format Rupiah di seluruh aplikasi (resource lama tampil `IDR 12,500.00` karena locale `en`, PO tampil `Rp 12.500,00` lewat `money('IDR', locale: 'id')`).
  evidence: `Number::currency(12500, 'IDR')` dengan locale app `en` menghasilkan `IDR 12,500.00`; SPEC dan test AC-7 menuntut `Rp`.
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/1-buat-draft-po-dari-produk-kritis.md`
  summary: Tambah test cabang sekunder story 1 (Repeater tanpa `fake()`, notifikasi ajukan PO kosong lewat UI, 403 edit PO non-draft untuk admin, dua draft simultan).
  evidence: Sudah terdaftar di checklist ATDD bagian "Cabang sekunder" untuk `/bmad-testarch-automate`; belum ada test yang menjalankannya.
