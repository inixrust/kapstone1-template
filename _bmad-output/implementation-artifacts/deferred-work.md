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
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/2-persetujuan-po-oleh-admin.md`
  summary: Perbaiki `brownfield.md`, `AGENTS.md`, dan komentar `RoleSeeder` yang menyebut admin melewati semua Policy lewat Shield `intercept_gate = before`.
  evidence: `config/filament-shield.php` memakai `define_via_gate = false`, sehingga `FilamentShieldServiceProvider` tidak memasang `Gate::before`; admin lolos hanya karena memegang semua permission, dan ability kustom yang tidak memeriksa permission (mis. `return false`) tetap menolak admin — ditemukan saat AC-1/AC-2 story 2 gagal.
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/2-persetujuan-po-oleh-admin.md`
  summary: Tambah test cabang sekunder story 2 (catatan tersimpan saat menyetujui, `decided_at` terisi, `reject('')` di domain, aksi tersembunyi untuk admin pembuat dan PO non-diajukan).
  evidence: Terdaftar di checklist ATDD story 2 bagian "Cabang sekunder"; perilaku sudah diimplementasikan tetapi belum ada test yang menjalankannya.
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/3-penerimaan-barang-po.md`
  summary: Tambah test cabang sekunder story 3 (receive tanpa login, notifikasi sukses/gagal, aksi Terima hilang setelah diterima, bulk delete movement ber-PO, regresi edit/hapus movement non-PO, rollback migrasi `purchase_order_id`).
  evidence: Terdaftar di checklist ATDD story 3 bagian "Cabang sekunder"; perilaku sudah diimplementasikan tetapi belum ada test yang menjalankannya.
- source_spec: `_bmad-output/specs/spec-purchase-order/stories/3-penerimaan-barang-po.md`
  summary: Koreksi docblock `PurchaseOrder::submit()`/`ensureCanBeDecidedBy()` dan kontrak ATDD yang masih menyebut admin lolos lewat bypass `intercept_gate`.
  evidence: `define_via_gate = false`; Policy `receive` harus menyebut admin eksplisit agar admin bisa menerima — asumsi "admin lolos lewat bypass" di checklist ATDD story 3 tidak berlaku.
