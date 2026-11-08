- source_spec: `_bmad-output/implementation-artifacts/spec-filter-status-stok-produk.md`
  summary: Selaraskan `InventoryAnalyticsService::criticalStockProducts()` dengan scope `Product::withStockStatus()` / konstanta `LowStockMultiplier`.
  evidence: Service memakai `current_stock <= reorder_point * 1.5` tanpa ROUND; untuk reorder_point 7 dan stok 11, badge/filter menyatakan "Rendah" tetapi chart stok kritis tidak menampilkannya. Sudah ada sebelum perubahan ini.
- source_spec: `_bmad-output/implementation-artifacts/spec-filter-status-stok-produk.md`
  summary: Pertimbangkan enum `StockStatus` (label + warna) agar kunci `critical`/`low`/`safe` tidak tersebar sebagai string di model, tabel Produk, dan relation manager Supplier.
  evidence: String status muncul di `Product::stockStatus()`, scope, `ProductsTable` (opsi filter + warna badge), dan `ProductsRelationManager`; salah ketik tidak terdeteksi.
