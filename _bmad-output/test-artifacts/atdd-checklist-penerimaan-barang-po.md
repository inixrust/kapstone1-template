---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-11-08'
workflowType: 'testarch-atdd'
storyId: '3'
storyKey: 'penerimaan-barang-po'
storyFile: '_bmad-output/specs/spec-purchase-order/stories.yaml#3'
atddChecklistPath: '_bmad-output/test-artifacts/atdd-checklist-penerimaan-barang-po.md'
generatedTestFiles:
  - 'tests/Feature/PurchaseOrderReceivingTest.php'
  - 'tests/Pest.php'
  - 'tests/e2e/purchase-order.spec.ts'
  - 'tests/support/fixtures/purchase-order-fixture.ts'
  - 'tests/support/fixtures/index.ts'
  - 'tests/support/helpers/artisan.ts'
inputDocuments:
  - '_bmad-output/specs/spec-purchase-order/stories.yaml'
  - '_bmad-output/specs/spec-purchase-order/SPEC.md'
  - '_bmad-output/specs/spec-purchase-order/state-machines.md'
  - '_bmad-output/specs/spec-purchase-order/brownfield.md'
  - '_bmad-output/test-artifacts/test-design-epic-purchase-order.md'
  - '_bmad-output/test-artifacts/atdd-checklist-persetujuan-po-oleh-admin.md'
  - 'AGENTS.md'
  - 'tests/README.md'
  - 'tests/Pest.php'
  - 'tests/Feature/PurchaseOrderApprovalTest.php'
  - 'tests/Feature/StockMovementTest.php'
  - 'tests/Feature/AuthorizationMatrixTest.php'
  - 'tests/e2e/authorization.spec.ts'
  - 'tests/e2e/category.spec.ts'
  - 'tests/support/fixtures/category-fixture.ts'
  - 'app/Models/PurchaseOrder.php'
  - 'app/Models/StockMovement.php'
  - 'app/Observers/StockMovementObserver.php'
  - 'app/Observers/PurchaseOrderObserver.php'
  - 'app/Policies/PurchaseOrderPolicy.php'
  - 'app/Policies/StockMovementPolicy.php'
  - 'app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php'
  - 'app/Filament/Resources/StockMovements/Pages/EditStockMovement.php'
  - 'playwright.config.ts'
  - '_bmad/tea/config.yaml'
acceptanceCriteria:
  - { id: 'AC-1', idSource: 'generated', text: 'Penerimaan PO disetujui oleh staff membuat satu movement IN per item: qty = jumlah item, merujuk PO, user_id = penerima, catatan memuat nomor PO' }
  - { id: 'AC-2', idSource: 'generated', text: 'current_stock tiap produk bertambah tepat sebesar jumlah item; harga_modal produk tidak berubah' }
  - { id: 'AC-3', idSource: 'generated', text: 'PO menjadi berstatus diterima lewat aksi Terima' }
  - { id: 'AC-4', idSource: 'generated', text: 'Menerima PO yang bukan disetujui (termasuk yang sudah diterima) ditolak tanpa movement baru dan stok tetap, termasuk oleh admin' }
  - { id: 'AC-5', idSource: 'generated', text: 'Penerimaan atomik: kegagalan satu item membatalkan semua movement dan status tetap disetujui' }
  - { id: 'AC-6', idSource: 'generated', text: 'Movement yang merujuk PO tidak dapat diubah atau dihapus di domain, termasuk oleh admin' }
  - { id: 'AC-7', idSource: 'generated', text: 'Staff dan manager tidak dapat mengedit/menghapus movement yang merujuk PO (Policy + halaman edit)' }
  - { id: 'AC-8', idSource: 'generated', text: 'Aksi Terima tersedia untuk manager dan staff hanya pada PO disetujui' }
  - { id: 'AC-9', idSource: 'generated', text: 'Transisi terima tercatat di audit trail dengan causer = penerima' }
  - { id: 'AC-10', idSource: 'generated', text: 'E2E alur utama manager → admin → staff: stok produk naik sebesar jumlah PO dan movement menunjuk PO' }
---

# ATDD Checklist - Epic purchase-order, Story 3: Penerimaan barang PO

**Date:** 2026-11-08
**Author:** Rustan
**Primary Test Level:** Pest Feature — Domain (model) + Livewire (Filament); E2E Playwright untuk alur penutup epik

---

## Story Summary

CAP-3: manager atau staff mencatat penerimaan PO yang disetujui; setiap item menjadi movement IN yang merujuk PO (migrasi baru), movement itu dikunci dari edit/hapus, dan PO menjadi diterima. Story ini menutup epik dengan E2E Playwright alur manager → admin → staff.

**As a** staff gudang
**I want** mencatat penerimaan PO yang disetujui dengan satu aksi
**So that** stok bertambah otomatis dan setiap barang masuk tertelusur ke PO-nya

---

## Acceptance Criteria

Story tidak punya id AC; registry diturunkan berurutan dari deskripsi story → CAP-3 success → Constraints/Assumptions SPEC → success signal (semua `generated`). Prioritas dari `test-design-epic-purchase-order.md`.

| ID | Sumber | Kriteria | Prioritas | Skenario test design | Risiko |
| --- | --- | --- | --- | --- | --- |
| AC-1 | deskripsi + CAP-3 success + Assumptions | Satu movement IN per item, qty = jumlah, merujuk PO, `user_id` = penerima, catatan memuat nomor PO | P0 | PO-P0-07 | R-002 |
| AC-2 | CAP-3 success + Assumptions | Stok tiap produk naik tepat sebesar jumlah; `harga_modal` tetap | P0 | PO-P0-07 | R-002 |
| AC-3 | deskripsi + CAP-3 success | PO menjadi diterima (aksi Terima) | P0 | PO-P0-07 | R-005 |
| AC-4 | CAP-3 success + state-machines | Menerima PO bukan disetujui ditolak, tanpa movement, juga admin | P0 | PO-P0-08 | R-002, R-005 |
| AC-5 | Assumptions (atomik) | Kegagalan item ke-n → rollback penuh | P0 | PO-P0-09 | R-002 |
| AC-6 | deskripsi + Assumptions | Movement ber-PO tidak bisa diubah/dihapus di domain, juga admin | P0 | PO-P0-10 | R-003 (BLOCK) |
| AC-7 | brownfield (jebakan movement) | Staff/manager tidak bisa edit/hapus movement ber-PO | P0 | PO-P0-11 | R-003 |
| AC-8 | CAP-3 intent + Assumptions | Aksi Terima untuk manager & staff hanya pada PO disetujui | P1 | PO-P1-08 | R-006 |
| AC-9 | Constraints (audit) | Transisi terima tercatat, causer = penerima | P0 | PO-P0-14 (terima) | R-010 |
| AC-10 | deskripsi (E2E) + Success signal | E2E manager → admin → staff, stok naik, movement menunjuk PO | P0 | PO-P0-15 (+ PO-P1-10 sebagai assertion sekunder) | R-001, R-002, R-004, R-012 |

---

## Story Integration Metadata

- **Story ID:** `3`
- **Story Key:** `penerimaan-barang-po` (slug judul; story dari `stories.yaml`, belum ada berkas story BMM)
- **Story File:** `_bmad-output/specs/spec-purchase-order/stories.yaml` (entri `id: "3"`)
- **Checklist Path:** `_bmad-output/test-artifacts/atdd-checklist-penerimaan-barang-po.md`
- **Dependensi:** story 1 (`77c6a7d`) dan story 2 (`d871b12`) — sudah selesai.

---

## Keputusan workflow

- **Stack:** `fullstack` (Laravel + Vite/Playwright). Level "API" diganti Pest Feature Domain/Livewire (aplikasi tanpa REST API, sesuai test design).
- **Mode generasi:** AI generation + perekaman ringan: snapshot aria halaman `Create Purchase Order` sebagai manager (skrip Playwright sementara, sudah dihapus) untuk memastikan selector combobox `Supplier` → `Search` → option.
- **Mode eksekusi:** sequential (tanpa subagent).
- **Penanda red phase:** Pest `->skip('ATDD red phase: story 3 penerimaan purchase order belum diimplementasikan')` pada setiap leaf; Playwright `test.skip(...)` dengan komentar alasan di baris atas (syarat hook `tea-enforce`).
- **Playwright Utils / Pact:** tidak berlaku — `@seontechnologies/playwright-utils` tidak terpasang (test design melarang menambah dependensi), jadi spec memakai `test`/`expect` dari `tests/support/fixtures` dan `waitForResponse` seperti `category.spec.ts`. Tidak ada kontrak API.
- **Knowledge fragments:** core (`data-factories`, `component-tdd`, `test-quality`, `test-healing-patterns`), frontend (`selector-resilience`, `timing-debugging`), CLI (`playwright-cli`), tradisional (`fixture-architecture`, `network-first`), backend (`test-levels-framework`, `test-priorities-matrix`, `ci-burn-in`).

---

## Red-Phase Test Scaffolds Created

10 leaf (satu per AC): 9 Pest (15 kasus setelah dataset) + 1 E2E. Judul tiap leaf memuat tepat satu `[P#] AC-<n>`.

### `tests/Feature/PurchaseOrderReceivingTest.php` (baru)

| Test | Level | Kasus | Kegagalan red yang terverifikasi |
| --- | --- | --- | --- |
| `[P0] AC-1 creates one in movement per item referencing the purchase order when staff receives it` | Domain | 1 | `Call to undefined method PurchaseOrder::receive()` |
| `[P0] AC-2 increases each product current stock by exactly the received quantity without changing its cost` | Domain | 1 | idem |
| `[P0] AC-3 marks the purchase order as received when staff confirms the receive action` | Livewire | 1 | aksi `receive` tidak ada di `ViewPurchaseOrder` |
| `[P0] AC-4 rejects receiving a purchase order that is not approved without new movements even for admin` | Domain | 4 (draft/submitted/rejected/received) | `BadMethodCallException` bukan `AuthorizationException` |
| `[P0] AC-5 rolls back every movement and keeps the purchase order approved when one item fails` | Domain | 1 | `toThrow(RuntimeException, 'Simulasi kegagalan…')` — kini `BadMethodCallException` |
| `[P0] AC-6 forbids updating or deleting a movement that references a purchase order even for admin` | Domain | 2 (update/delete) | `Unknown column 'purchase_order_id'` (migrasi belum ada) |
| `[P0] AC-7 denies staff and manager editing or deleting a movement that references a purchase order` | Domain + Livewire | 2 (staff/manager) | idem |
| `[P1] AC-8 shows the receive action to manager and staff only on approved purchase orders` | Livewire | 2 (manager/staff) | `assertActionVisible('receive')` |
| `[P0] AC-9 logs the receive transition with the receiving user as causer` | Domain | 1 | `receive()` belum ada |

AC-4 dan AC-6 memakai `adminUser()` sebagai pelaku dengan sengaja (bypass Shield: hanya penjagaan domain yang bisa membuatnya hijau). AC-7 dan AC-8 memakai `userWithRole('staff'|'manager')`. AC-5 memicu kegagalan dengan listener `StockMovement::creating` pada produk item terakhir, dipasang setelah setup.

### `tests/e2e/purchase-order.spec.ts` (baru)

| Test | Kegagalan red yang terverifikasi |
| --- | --- |
| `[P0] AC-10 manager mengajukan, admin menyetujui, staff menerima, dan stok bertambah sebesar jumlah PO` | Timeout `getByRole('button', { name: 'Terima' })` — langkah manager (buat + ajukan) dan admin (setujui) sudah lolos dengan selector nyata |

Assertion sekunder dalam leaf yang sama (setelah assertion kriteria): baris movement di `/admin/stock-movements` memuat nomor PO, dan detail PO untuk staff tidak merender `Harga per Item`, `Subtotal`, atau `Rp …` (PO-P1-10).

### Infrastruktur pendukung

- `tests/Pest.php`: helper baru `purchaseOrderWithItems(string $status, array $quantities, array $attributes = [])` — satu produk kritis baru (stok 5 lewat movement IN) per item, status dipasang lewat query builder, relasi `items.product` dimuat. Helper lama tidak diubah.
- `tests/support/helpers/artisan.ts`: `runTinker()` menjalankan `php artisan tinker --execute` lewat `docker compose exec -T -u sail laravel.test` (tanpa shell; data di-encode base64).
- `tests/support/fixtures/purchase-order-fixture.ts` (digabung di `index.ts`):
  - `createCriticalProduct()` — kategori, supplier, dan produk berprefiks `E2E PO` (stok 0, reorder point 10 → kritis, harga modal 15000); tidak pernah memakai produk seed.
  - `currentStockOf(productId)`, `purchaseOrderNumberFor(supplierId)` — baca nilai dari database dev.
  - Cleanup sebelum dan sesudah test lewat query builder (sengaja: PO diterima dan movement ber-PO terkunci di domain): activity log subjek terkait, movement, PO (item ikut cascade), produk, supplier, kategori berprefiks `E2E PO`. Terverifikasi: setelah run aktivasi, 0 supplier/kategori/PO tersisa.

---

## Kontrak implementasi yang diasumsikan scaffold

Opsi paling sederhana yang konsisten dengan kode story 1–2 (asumsi; bila dev memilih nama lain, sesuaikan test secara eksplisit):

1. **Migrasi baru** (bukan mengubah `2026_09_04_073157_create_stock_movements_table`), dengan `down()`: `stock_movements.purchase_order_id` `foreignId()->nullable()->constrained()` (`restrictOnDelete`/default; teardown E2E menghapus movement lebih dulu). `StockMovement::purchaseOrder()` (`BelongsTo`) dan `PurchaseOrder::stockMovements()` (`HasMany`). Tambahkan `purchase_order_id` ke `logOnly` activity movement bila perlu.
2. **Method domain** `PurchaseOrder::receive(): void`, mengikuti pola `submit()`/`approve()`:
   - Melempar `AuthorizationException` bila PO bukan `Approved` (termasuk sudah `Received`), tidak ada user login, atau user `cannot('receive', $this)`. Tidak ada larangan pembuat (berbeda dari keputusan).
   - Dalam `DB::transaction`: PO di-`lockForUpdate` dan statusnya dicek ulang; per item (urutan id) `StockMovement::create([... 'type' => In, 'qty' => $item->qty, 'note' => "Penerimaan {$this->number}", 'user_id' => Auth::id(), 'purchase_order_id' => $this->id])` lewat model (Observer me-replay stok); status `Received` disimpan lewat `save()` di akhir transaksi.
   - Exception dari pembuatan movement tidak ditelan (AC-5 mengharapkan `RuntimeException` asli diteruskan).
   - Catatan dibuat pendek (`Penerimaan PO-YYYYMM-####`): kolom `Catatan` di tabel movement memotong 40 karakter dan E2E mencari nomor PO di baris tabel.
   - Tidak mengubah `harga_modal` produk.
3. **Policy** `PurchaseOrderPolicy::receive()`: `true` hanya bila status `Approved` dan user `hasAnyRole(['manager', 'staff'])` (pola `canDecide`/`adjust`; admin lolos lewat bypass). Tidak ada perubahan permission di `RoleSeeder`.
4. **Kunci movement ber-PO**:
   - Domain: di `StockMovementObserver` `updating()` dan `deleting()` lempar `AuthorizationException` bila `purchase_order_id` (nilai original) terisi — berlaku untuk semua user dan konteks, termasuk admin.
   - Policy: `StockMovementPolicy::update()`/`delete()` mengembalikan `false` bila `purchase_order_id` terisi, sebelum cek permission.
   - Bulk delete di `StockMovementsTable` tetap ada; penjagaan domain menolak movement ber-PO (lihat cabang sekunder).
5. **Filament** `ViewPurchaseOrder`: header action `receive` (label `Terima`, ikon mis. `Heroicon::OutlinedInboxArrowDown`, `requiresConfirmation()`, heading `Terima purchase order?`, tombol submit `Terima`), `->authorize('receive')`, terlihat hanya untuk PO `Approved`; `AuthorizationException` ditangkap menjadi notifikasi danger (`Purchase order gagal diterima`), sukses → `Purchase order diterima`.
6. **Tidak ada notifikasi** email/database (non-goal).

---

## Required data factories & seeder

- [x] Helper `purchaseOrderWithItems()` (tests/Pest.php) — tidak butuh factory baru.
- [ ] Opsional untuk automate: state `received`/`approved` di `PurchaseOrderFactory`, state `forPurchaseOrder()` di `StockMovementFactory`.
- [ ] Migrasi `purchase_order_id` (kontrak #1).

---

## Implementation Checklist (dev, story 3)

Aktifkan satu test per tugas dengan menghapus `->skip(...)` / `.skip`, lalu jalankan
`docker compose exec -T -u sail laravel.test php artisan test --compact tests/Feature/PurchaseOrderReceivingTest.php --filter='AC-<n> '`.

- [ ] Migrasi `stock_movements.purchase_order_id` + relasi → prasyarat AC-6, AC-7
- [ ] Kunci movement ber-PO di Observer → aktifkan AC-6
- [ ] `StockMovementPolicy` update/delete false untuk movement ber-PO → aktifkan AC-7
- [ ] `PurchaseOrder::receive()` + Policy `receive` → aktifkan AC-1, AC-2, AC-4, AC-5, AC-9
- [ ] Aksi Filament `Terima` di `ViewPurchaseOrder` → aktifkan AC-3, AC-8
- [ ] Jalankan `ui-consistency:adjusting` → `implementing` → `verifying` untuk aksi header baru
- [ ] Hapus `test.skip` di `tests/e2e/purchase-order.spec.ts` (beserta komentar red-phase) → `npx playwright test tests/e2e/purchase-order.spec.ts` (AC-10)
- [ ] `vendor/bin/pint --dirty --format agent`; minta user menjalankan full suite Pest dan `npm run test:e2e`

---

## Cabang sekunder untuk green phase (`/bmad-testarch-automate`)

- AC-1/AC-2: PO dengan produk yang sama di PO lain; stok produk yang tidak ada di PO tidak berubah.
- AC-3: notifikasi sukses/gagal; aksi `Terima` hilang setelah PO diterima.
- AC-4: penerimaan oleh user tanpa login ditolak; panggilan Livewire `callAction('receive')` pada PO non-disetujui tidak membuat movement.
- AC-5: tidak ada activity `received` bila rollback.
- AC-6/AC-7: bulk delete (`DeleteBulkAction`) yang memuat movement ber-PO ditolak tanpa mengubah stok; aksi `DeleteAction` di `EditStockMovement` tidak tampil.
- PO-P1-09 (regresi): movement tanpa PO tetap bisa diedit staff/dihapus manager dan replay stok benar dengan kolom baru.
- PO-P2-04: rollback migrasi `purchase_order_id` bersih.
- PO-P3-03: penerimaan PO 50 item sukses; durasi dicatat (informasi, ambang UNKNOWN).
- PO-P1-10 sebagai test E2E terpisah bila diinginkan: daftar PO staff tanpa kolom `Total`.
- AC-8: `expect(userWithRole('staff')->can('receive', $approvedPo))->toBeTrue()` sebagai baris matriks.

---

## Validasi (step 5)

- Pest berkas PO + audit + stok + masking (`PurchaseOrderReceivingTest`, `PurchaseOrderApproval*`, `PurchaseOrderDraft*`, `AuditTrailTest`, `StockMovementTest`, `CostMaskingTest`): **141 passed, 15 skipped** — test lama tetap hijau, semua scaffold ter-skip.
- Aktivasi sementara Pest (salinan tanpa `->skip`, lalu dihapus): **15/15 gagal** karena fitur belum ada — `receive()` tidak ada, aksi `receive` tidak ada, kolom `purchase_order_id` tidak ada. Tidak ada kegagalan karena setup salah.
- Aktivasi sementara E2E (salinan tanpa `.skip`, lalu dihapus): gagal di tombol `Terima`; langkah sebelumnya lolos; cleanup fixture meninggalkan 0 data `E2E PO`. `npx playwright test --list` mengenali spec.
- Pint: passed. Hook `tea-enforce` menerima spec (skip terdokumentasi, ada assertion).
- Setiap leaf memuat tepat satu id AC dari registry; 10 AC → 10 leaf.
- Sesi CLI/browser perekaman ditutup; skrip sementara di `/tmp` dihapus.
- Catatan lingkungan: warning Pest `file_put_contents(vendor/pestphp/pest/.temp/test-results): Permission denied` (berkas cache milik root di `vendor/`, sudah ada sejak story 1; tidak memengaruhi hasil).

---

## Asumsi dry run

- Story key dari slug judul (`penerimaan-barang-po`) karena story berasal dari `stories.yaml`.
- PO-P1-10 (DOM staff tanpa harga) tidak dijadikan leaf sendiri karena perilakunya sudah ada sejak story 1 (tidak bisa red); dicakup sebagai assertion sekunder di AC-10.
- Fixture E2E membuat data dan membersihkan lewat `artisan tinker` di container Sail, bukan UI: movement ber-PO dan PO diterima memang tidak bisa dihapus lewat UI (R-003/R-012).
- Penerimaan tidak menerapkan larangan pembuat; manager pembuat boleh menerima PO-nya sendiri (SPEC tidak melarang).
- Tidak ada perubahan kode aplikasi; hanya test, helper/fixture test, dan checklist ini. Tidak ada commit (fasilitator yang meng-commit).

## Next recommended workflow

`bmad-build` / dev story 3 (aktifkan scaffold satu per satu), lalu `/bmad-testarch-automate` untuk cabang sekunder, kemudian full suite Pest + `npm run test:e2e`.
