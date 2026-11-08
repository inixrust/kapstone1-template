---
title: 'Story 1 — Buat draft PO dari produk kritis'
type: 'feature'
created: '2026-11-08'
status: 'done'
baseline_commit: 'eec90336b8e84ac4a2c80b3aaf062f4b224de590'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-purchase-order/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-purchase-order/state-machines.md'
  - '{project-root}/_bmad-output/specs/spec-purchase-order/brownfield.md'
  - '{project-root}/_bmad-output/test-artifacts/atdd-checklist-buat-draft-po-dari-produk-kritis.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Pesanan ulang produk kritis dibuat di luar sistem, sehingga tidak tercatat dan tidak bisa disetujui atau ditelusuri.

**Approach:** Resource Filament Purchase Order (grup Transaksi): manager memilih supplier, item awal diisi produk kritis supplier itu dengan harga `harga_modal` (boleh diubah), lalu mengajukan PO (draft → diajukan). Aturan status dan penguncian ditegakkan di model, bukan hanya Policy.

## Boundaries & Constraints

**Always:** Nomor `PO-YYYYMM-####` urut per bulan, unik di DB, diberikan saat dibuat; status awal selalu draft; `created_by` = user login. Transisi status hanya sesuai `state-machines.md` (ditolak di domain dengan `AuthorizationException`, termasuk admin). Item hanya produk milik supplier PO; supplier tidak bisa diganti; item/hapus PO hanya saat draft; PO tanpa item tidak bisa diajukan. Harga per item, subtotal, total digate `can('viewCost', Product::class)` (hilang total untuk staff). Activity PO: log `purchase_order`, hanya `status`, causer = pelaku. Label Bahasa Indonesia lewat `->label()`. Ekspektasi test ATDD tidak diubah; hanya `->skip()` yang dihapus.

**Never:** Mengubah migrasi lama; menulis `current_stock`; notifikasi; aksi setujui/tolak/terima (story 2–3); mengubah definisi produk kritis (`withStockStatus('critical')`).

**Asumsi (dry run):** label status `Draft/Diajukan/Disetujui/Ditolak/Diterima`; FK `supplier_id` dan `product_id` `restrictOnDelete` (supplier/produk yang dipakai PO tidak bisa dihapus, demi jejak); `qty` item awal kosong (manager mengisi); tidak ada bulk delete di tabel PO.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected | Error Handling |
|---|---|---|---|
| Draft baru | 2 PO November, 1 Desember | `PO-202611-0002`, `PO-202612-0001`, draft | — |
| Pilih supplier | supplier punya produk kritis + aman | item awal hanya produk kritis supplier itu, harga = harga_modal | — |
| Ajukan | draft ≥1 item, manager | status submitted, activity old/new | — |
| Ajukan ilegal | non-draft / tanpa item | status tetap | `AuthorizationException`; UI: notifikasi danger |
| Kunci | non-draft: ubah item / hapus PO | tidak berubah | `AuthorizationException` |
| Validasi form | qty 0 / harga −1 / produk ganda | form error, tidak tersimpan | error field |

</frozen-after-approval>

## Code Map

- `app/Models/StockMovement.php`, `app/Observers/StockMovementObserver.php` -- preseden `LogsActivity` + penjagaan domain melempar `AuthorizationException`.
- `app/Enums/StockMovementType.php` -- pola enum `HasLabel`/`HasColor`.
- `app/Models/Product.php` -- scope `withStockStatus('critical')`, `harga_modal` decimal(15,2); jangan diubah.
- `app/Policies/StockMovementPolicy.php` -- pola Policy Shield + ability kustom.
- `database/seeders/RoleSeeder.php` -- `DOMAIN_RESOURCES`, staff read-only lewat `STAFF_READ_ACTIONS`; `ShieldSeeder` otomatis membuat permission resource baru.
- `app/Filament/Resources/StockMovements/*` -- resource referensi (grup Transaksi, Schemas/Tables terpisah, Create/Edit/List).
- `app/Filament/Resources/Products/Schemas/ProductForm.php`, `Tables/ProductsTable.php` -- gate `viewCost` + `prefix('Rp')`/`money('IDR')`.
- `tests/Pest.php` -- helper `purchaseOrderWithItem()`, `productWithStockFor()` (sudah ada).

## Tasks & Acceptance

**Execution:**
- [x] `database/migrations/*_create_purchase_orders_table.php` -- tabel `purchase_orders` (number unique, supplier_id, status, created_by nullable) + `purchase_order_items` (FK PO cascade, product, qty, unit_price decimal(15,2), unique PO+produk), dengan `down()`.
- [x] `app/Enums/PurchaseOrderStatus.php` -- 5 kasus + label/warna + `canTransitionTo()`.
- [x] `app/Models/PurchaseOrder.php`, `PurchaseOrderItem.php` + factory -- relasi, `submit()`, `total()`, event model untuk penomoran (`lockForUpdate`), status, penjagaan.
- [x] `app/Policies/PurchaseOrderPolicy.php` -- Shield + `submit`; update/delete hanya draft.
- [x] `database/seeders/RoleSeeder.php` -- `PurchaseOrder` ke manager (domain) dan staff (read).
- [x] `app/Filament/Resources/PurchaseOrders/**` -- Resource, Form (supplier live → Repeater items), Table, Infolist, halaman List/Create/Edit/View, aksi `submit` "Ajukan".
- [x] Test ATDD -- hapus `->skip()` di 4 berkas test.

**Acceptance Criteria:** AC-1 … AC-18 di checklist ATDD, semua hijau; test lama tetap hijau.

## UI checklist (ui-consistency, referensi StockMovementResource — 4 resource, 4 kandidat)

- [x] Resource: `$navigationGroup = 'Transaksi'`, `$navigationLabel/$modelLabel/$pluralModelLabel` Indonesia, Heroicon outlined -- pola 4 dari 4.
- [x] Schemas/Tables di kelas `configure()` terpisah -- 4 dari 4.
- [x] List: header `CreateAction` -- 5 berkas; Edit: header `DeleteAction` -- 5 berkas.
- [x] Select relasi: `->relationship()->searchable()->preload()` -- 4 berkas.
- [x] Kolom enum `->badge()`, `->defaultSort(...)` -- 6 berkas.
- [x] Harga: input `->prefix('Rp')`, tampilan `->money('IDR')`, `->visible(fn (): bool => Auth::user()?->can('viewCost', Product::class) ?? false)` -- 2 berkas, aturan AGENTS.md.
- [x] Posisi tanpa anggota (View page/infolist, Repeater, aksi kustom): pakai komponen bawaan Filament, label Indonesia -- tidak ada konvensi proyek (level framework).
- [x] Warna lewat tema panel (`color` Filament: success/danger/warning/gray), tanpa literal.
- [x] Tidak disalin dari referensi: `DeleteBulkAction` (hapus massal akan menabrak penjagaan non-draft).

## Implementation Notes

- Diimplementasikan langsung (tanpa subagent; aturan sesi melarang subagent tanpa permintaan eksplisit). Review step 4 dijalankan inline oleh agen yang sama (bukan reviewer independen).
- Penjagaan domain di `PurchaseOrderObserver` (nomor, status draft, `created_by`, supplier/nomor tetap, transisi via `PurchaseOrderStatus::canTransitionTo()`, hapus hanya draft) dan `PurchaseOrderItemObserver` (item hanya saat draft, produk milik supplier PO). `PurchaseOrder::submit()` cek status, ability `submit`, dan item.
- activitylog v5 menyimpan perubahan di `attribute_changes`; test AC-8/AC-13 membaca `properties.attributes/old`, jadi `PurchaseOrder::beforeActivityLogged()` menyalinnya ke `properties` (ekspektasi test tidak diubah).
- `money('IDR')` dengan locale app `en` tampil `IDR …`; harga PO memakai `money('IDR', locale: 'id')` → `Rp …` (AC-7). Locale app tidak diubah.
- Ability `submit` memakai permission `Update:PurchaseOrder` (tanpa permission kustom Shield). Staff hanya `ViewAny`/`View`.
- `EditPurchaseOrder::authorizeAccess()` menolak (403) PO non-draft juga untuk admin; tabel tanpa bulk delete.
- Migrasi + `ShieldSeeder` + `RoleSeeder` sudah dijalankan di database dev.
- Belum diverifikasi di browser (izin Playwright MCP ditolak): pemilihan supplier di form nyata (kunci Repeater UUID).

## Spec Change Log

## Review Triage Log

| Temuan | Verdict | Bukti / rute |
|---|---|---|
| `lockForUpdate` penomoran tidak berefek di luar transaksi (pemanggilan `create()` langsung) | low | Filament `CreateRecord` membuka transaksi; jalur lain dijaga unique index (error, bukan duplikat). Ditolak: fix menambah kompleksitas. |
| Hapus produk/supplier yang dipakai PO → 500 (FK restrict) | medium | Nyata, tetapi menyentuh resource lama di luar story → defer. |
| Format `Rp` PO beda dengan `IDR` di resource lama | low | Nyata; konsistensi lintas app → defer. |
| Cabang sekunder tanpa test (Repeater nyata, ajukan kosong via UI, 403 admin edit non-draft, konkurensi) | medium | Terdaftar di checklist ATDD untuk automate → defer. |
| Nomor > 9999/bulan merusak urutan string | low | Tidak realistis untuk satu gudang; ditolak. |
| `PurchaseOrderItemFactory` tanpa `product_id` eksplisit melempar (supplier beda) | low | Disengaja dan didokumentasikan di docblock; ditolak. |
| `properties` menduplikasi `attribute_changes` | low | Diperlukan agar test tidak diubah; ditolak. |

## Verification

**Commands:**
- `docker compose exec -T -u sail laravel.test php artisan test --compact tests/Feature/PurchaseOrderDraftTest.php tests/Feature/AuthorizationMatrixTest.php tests/Feature/CostMaskingTest.php tests/Feature/AuditTrailTest.php` -- semua lulus, tanpa skip.
- `docker compose exec -T -u sail laravel.test vendor/bin/pint --dirty --format agent` -- bersih.
