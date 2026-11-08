---
stepsCompleted: ['step-01-load-context', 'step-02-discover-tests', 'step-03-map-criteria', 'step-04-analyze-gaps', 'step-05-gate-decision']
lastStep: 'step-05-gate-decision'
lastSaved: '2026-11-08'
coverageBasis: 'acceptance_criteria'
oracleConfidence: 'high'
oracleResolutionMode: 'formal_requirements'
oracleSources: ['_bmad-output/test-artifacts/test-design-epic-purchase-order.md', '_bmad-output/specs/spec-purchase-order/SPEC.md', '_bmad-output/specs/spec-purchase-order/stories.yaml', '_bmad-output/specs/spec-purchase-order/state-machines.md']
externalPointerStatus: 'not_used'
collectionStatus: 'COLLECTED'
sourceSha: '402476df7e6e165ea27822c34a794e4850d277f6'
tempCoverageMatrixPath: '/tmp/tea-trace-coverage-matrix-2026-11-08T10-46-07-215687+00-00.json'
---

# Traceability Matrix & Gate Decision - Epic purchase-order (Purchase Order ke Supplier)

**Target:** epic `purchase-order` (gate_type: epic)
**Tanggal:** 2026-11-08
**Evaluator:** Rustan (TEA, mode deterministic)
**Commit:** `402476df7e6e165ea27822c34a794e4850d277f6` (branch `dryrun/kapstone1`)

---

## Step 1 — Oracle Cakupan

- **Oracle:** test design epik `_bmad-output/test-artifacts/test-design-epic-purchase-order.md` (diminta eksplisit). 32 skenario: P0-01..15, P1-01..10, P2-01..04, P3-01..03.
- **coverageBasis:** `acceptance_criteria` · **oracleResolutionMode:** `formal_requirements` · **oracleConfidence:** `high` · **externalPointerStatus:** `not_used`.
- **Alasan:** oracle formal tersedia dan sudah memetakan risiko R-001..R-013 ke skenario berprioritas; SPEC, `stories.yaml`, dan `state-machines.md` dipakai sebagai konteks pendukung.
- **Knowledge fragment:** `test-priorities-matrix.md`, `risk-governance.md`, `probability-impact.md`, `test-quality.md`, `selective-testing.md`.
- **Level test** (sesuai test design, stack tanpa REST API): *Domain* → `api`, *Livewire* → `component`, *Playwright* → `e2e`.

## Step 2 — Penemuan Test

- **Berkas:** 6 berkas `tests/Feature/PurchaseOrder*Test.php`, bagian PO di `CostMaskingTest.php`, `AuditTrailTest.php`, `AuthorizationMatrixTest.php`, serta `tests/e2e/purchase-order.spec.ts` (+ fixture `tests/support/fixtures/purchase-order-fixture.ts`).
- **Eksekusi Pest (run ini):** `docker compose exec -T -u sail laravel.test php artisan test --compact` untuk 9 berkas tersebut → **212 passed (566 assertions), 77,5 dtk**, 0 skip/todo.
- **E2E:** tidak dijalankan dalam run trace ini (analisis statis; collection_mode `contract_static`). Tidak ada `test.skip`/`fixme`.
- **Test di luar oracle (valid, tidak dihitung):** `PurchaseOrderDraftGuardsTest.php:196` (total PO), `PurchaseOrderApprovalGuardsTest.php:133` (mass assignment kolom keputusan), `:149` (admin pemutus dihapus), `PurchaseOrderReceivingGuardsTest.php:78` (terima tanpa login), `:143` (activity movement hasil PO).

### Live Verification Results

```json
{
  "liveManifestHeader": {
    "present": false,
    "results_file": "_bmad-output/test-artifacts/live-verification-results.json",
    "source_sha": "",
    "observed_at": "",
    "producer": "",
    "read_error": "",
    "current_source_sha": "402476df7e6e165ea27822c34a794e4850d277f6"
  },
  "liveRecords": []
}
```

### Coverage Heuristics

- **Endpoint:** tidak berlaku (Filament/Livewire, tanpa REST API) → 0 gap.
- **Auth/authz negative path:** ada untuk semua kriteria otorisasi (staff/manager ditolak, admin pembuat ditolak, tanpa login ditolak) → 0 gap.
- **Error path:** ada (rollback penerimaan, transisi ilegal, validasi item, catatan kosong, PO tanpa item) → 0 kriteria happy-path-only.
- **UI journey/state:** `not_applicable` (oracle formal, bukan sintetis).

---

## PHASE 1: REQUIREMENTS TRACEABILITY

### Coverage Summary

| Priority  | Total | FULL | Coverage % | Status |
| --------- | ----- | ---- | ---------- | ------ |
| P0        | 15    | 15   | 100%       | ✅ PASS |
| P1        | 10    | 9    | 90%        | ✅ PASS (target 90%) |
| P2        | 4     | 3    | 75%        | ℹ️ informasional |
| P3        | 3     | 2    | 67%        | ℹ️ informasional |
| **Total** | **32**| **29** | **91%**  | ✅ PASS |

FULL 29 · PARTIAL 2 (PO-P1-10, PO-P3-01) · NONE 1 (PO-P2-04) · UNIT-ONLY/INTEGRATION-ONLY 0.

### Detailed Mapping

#### PO-P0-01: Admin pembuat menyetujui/menolak PO sendiri -> exception, status tetap diajukan, tanpa activity baru (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-001
- **Tests:**
  - `tests/Feature/PurchaseOrderApprovalTest.php:51` (api) — [P0] AC-4 forbids the creator from deciding their own purchase order even when the creator is admin
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:14` (component) — [P0] hides the approve and reject actions from the admin who created the purchase order
- **Justifikasi:** Admin sebagai created_by dan pelaku; approve & reject (dataset) melempar AuthorizationException, status tetap Submitted, jumlah activity tidak berubah; aksi UI tersembunyi untuk pembuat.
- **Considered and rejected:** tidak ada

#### PO-P0-02: Manager dan staff tidak bisa menyetujui/menolak (aksi tersembunyi + panggilan langsung ditolak) (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-001, R-006
- **Tests:**
  - `tests/Feature/PurchaseOrderApprovalTest.php:63` (component) — [P0] AC-5 forbids manager and staff from deciding a purchase order
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:24` (api) — [P0] authorizes approving and rejecting by role and status
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:98` (api) — [P1] refuses a decision without a logged-in user
- **Justifikasi:** userWithRole manager/staff x approve/reject: assertActionHidden + domain throw + status tetap; Policy dataset per role; tanpa login ditolak.
- **Considered and rejected:** tidak ada

#### PO-P0-03: Matriks otorisasi PurchaseOrder (staff: ViewAny/View/terima; manager: + Create/Update/Delete/ajukan; tanpa setujui/tolak) (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-006
- **Tests:**
  - `tests/Feature/AuthorizationMatrixTest.php:85` (api) — [P0] AC-6 enforces the purchase order authorization matrix
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:76` (api) — [P0] authorizes purchase order abilities by role and status
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:24` (api) — [P0] authorizes approving and rejecting by role and status
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:15` (api) — [P0] authorizes receiving by role and status
  - `tests/Feature/PurchaseOrderDraftTest.php:83` (component) — [P0] AC-5 lets the manager submit a draft purchase order
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:162` (api) — [P2] lets the manager who created the purchase order receive it
- **Justifikasi:** Permission Shield per role (AuthorizationMatrixTest) + ability kustom submit/approve/reject/receive per role dan status; kontrol positif manager mengajukan dan menerima.
- **Considered and rejected:** tidak ada

#### PO-P0-04: Staff tidak bisa membuat atau mengajukan PO (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-006
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:94` (component) — [P0] AC-9 forbids staff from creating or submitting a purchase order
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:67` (api) — [P0] rejects a direct submit by staff in the domain
- **Justifikasi:** Halaman create forbidden, aksi submit tersembunyi, submit langsung di domain oleh staff melempar exception; status tetap Draft.
- **Considered and rejected:** tidak ada

#### PO-P0-05: Transisi ilegal ditolak di domain, juga untuk admin (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-005
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:20` (api) — [P0] rejects illegal status transitions in the domain even for admin
  - `tests/Feature/PurchaseOrderApprovalTest.php:76` (api) — [P0] AC-6 rejects deciding a purchase order that is not submitted even for admin
  - `tests/Feature/PurchaseOrderDraftTest.php:107` (api) — [P0] AC-10 rejects submitting a non-draft purchase order even for admin
  - `tests/Feature/PurchaseOrderReceivingTest.php:61` (api) — [P0] AC-4 rejects receiving a purchase order that is not approved without new movements even for admin
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:168` (api) — [P1] always starts a purchase order as draft even when another status is supplied
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:109` (component) — [P1] offers no approve or reject action on a purchase order that is not submitted
- **Justifikasi:** Dataset 16 transisi ilegal via save() dengan adminUser; keputusan pada PO non-diajukan; submit non-draft; terima non-disetujui; status awal selalu draft; status dan activity tidak berubah.
- **Considered and rejected:** tidak ada

#### PO-P0-06: PO non-draft tidak bisa diubah item/harga atau dihapus, termasuk oleh admin (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-005
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:116` (api) — [P0] AC-11 locks items and deletion of a non-draft purchase order even for admin
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:50` (api) — [P0] blocks adding and removing items of a non-draft purchase order even for admin
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:94` (component) — [P0] forbids the edit page of a non-draft purchase order even for admin
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:102` (component) — [P1] offers no submit or edit action on a non-draft purchase order
- **Justifikasi:** Dataset diajukan/disetujui/ditolak/diterima dengan adminUser: update harga item, tambah/hapus item, hapus PO melempar exception; halaman edit forbidden; aksi edit tersembunyi.
- **Considered and rejected:** tidak ada

#### PO-P0-07: Penerimaan oleh staff: satu movement In per item, qty, purchase_order_id, user_id, catatan nomor PO; stok naik tepat; harga_modal tetap; PO diterima (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-002
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingTest.php:17` (api) — [P0] AC-1 creates one in movement per item referencing the purchase order when staff receives it
  - `tests/Feature/PurchaseOrderReceivingTest.php:35` (api) — [P0] AC-2 increases each product current stock by exactly the received quantity without changing its cost
  - `tests/Feature/PurchaseOrderReceivingTest.php:51` (component) — [P0] AC-3 marks the purchase order as received when staff confirms the receive action
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:97` (api) — [P1] leaves products and purchase orders outside the received order untouched
- **Justifikasi:** Semua atribut movement diassert; current_stock = sebelum + qty dan harga_modal tetap; aksi Livewire menandai Received; PO/produk lain tidak tersentuh.
- **Considered and rejected:** tidak ada

#### PO-P0-08: Menerima PO yang bukan disetujui (termasuk sudah diterima) ditolak tanpa movement baru, stok tetap (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-002, R-005
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingTest.php:61` (api) — [P0] AC-4 rejects receiving a purchase order that is not approved without new movements even for admin
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:30` (api) — [P0] refuses a second receipt from a stale copy of an already received purchase order
- **Justifikasi:** Dataset draft/diajukan/ditolak/diterima oleh admin: exception, jumlah movement dan stok tetap; salinan basi PO yang sudah diterima tidak bisa menerima ulang.
- **Considered and rejected:** tidak ada

#### PO-P0-09: Penerimaan atomik: gagal di item ke-n -> rollback penuh (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-002
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingTest.php:74` (api) — [P0] AC-5 rolls back every movement and keeps the purchase order approved when one item fails
- **Justifikasi:** Event creating melempar pada item terakhir; 0 movement PO, status tetap Approved, stok semua produk tetap.
- **Considered and rejected:** tidak ada

#### PO-P0-10: Movement ber-PO tidak bisa di-update/di-hapus di model, termasuk admin; stok tidak di-replay (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-003
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingTest.php:97` (api) — [P0] AC-6 forbids updating or deleting a movement that references a purchase order even for admin
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:45` (component) — [P0] keeps purchase order movements when manager bulk deletes them with other movements
- **Justifikasi:** Dataset update/delete dengan adminUser melempar exception, qty dan current_stock tetap; bulk delete manager melewati movement ber-PO. Mitigasi R-003 (BLOCK) terbukti.
- **Considered and rejected:** tidak ada

#### PO-P0-11: StockMovementResource: edit/hapus tidak tersedia untuk movement hasil PO bagi staff dan manager (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-003
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingTest.php:114` (component) — [P0] AC-7 denies staff and manager editing or deleting a movement that references a purchase order
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:45` (component) — [P0] keeps purchase order movements when manager bulk deletes them with other movements
- **Justifikasi:** Policy update/delete false untuk staff & manager, halaman edit forbidden, bulk delete tidak menghapus movement ber-PO.
- **Catatan/Gap:** Visibilitas tombol edit/hapus per baris tabel tidak diassert langsung; disimpulkan dari Policy (Filament mengotorisasi aksi lewat Policy yang sama).
- **Considered and rejected:** tidak ada

#### PO-P0-12: Staff: harga item, subtotal, total tersembunyi total di tabel PO dan halaman view/edit (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-004
- **Tests:**
  - `tests/Feature/CostMaskingTest.php:50` (component) — [P0] AC-7 hides purchase order item prices and totals from staff but not manager
  - `tests/e2e/purchase-order.spec.ts:4` (e2e) — [P0] AC-10 manager mengajukan, admin menyetujui, staff menerima, dan stok bertambah sebesar jumlah PO
- **Justifikasi:** Kolom total tersembunyi di tabel untuk staff (kontrol positif manager), view tanpa teks Rp; E2E: Harga per Item/Subtotal/Rp tidak dirender. Edit tidak dapat diakses staff (PO-P0-03/04); tidak ada relation manager item.
- **Considered and rejected:** tidak ada

#### PO-P0-13: Audit PO tidak memuat nilai harga (properti activity hanya status) (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-004, R-010
- **Tests:**
  - `tests/Feature/AuditTrailTest.php:100` (api) — [P0] AC-13 records only the status in purchase order activity, never prices
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:39` (api) — [P0] records only the status in decision activity, never prices or the note
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:62` (api) — [P0] records only the status in receive activity, never prices
- **Justifikasi:** Kunci properti activity = [status] untuk create/submit, approve/reject, receive; catatan penolakan tidak ikut.
- **Considered and rejected:** tidak ada

#### PO-P0-14: Setiap transisi (ajukan, setujui, tolak, terima) mencatat activity log PO dengan causer dan status lama/baru (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-010
- **Tests:**
  - `tests/Feature/AuditTrailTest.php:80` (api) — [P0] AC-8 logs the submit transition with the causer and old and new status
  - `tests/Feature/PurchaseOrderApprovalTest.php:86` (api) — [P0] AC-7 logs the approve and reject transitions with the deciding admin as causer
  - `tests/Feature/PurchaseOrderReceivingTest.php:142` (api) — [P0] AC-9 logs the receive transition with the receiving user as causer
- **Justifikasi:** Empat transisi: properties.old/attributes.status, causer_id = pelaku, log_name purchase_order.
- **Considered and rejected:** tidak ada

#### PO-P0-15: E2E alur utama manager -> admin -> staff; stok naik sebesar jumlah PO; movement menunjuk PO (P0)

- **Coverage:** FULL ✅
- **Risiko:** R-001, R-002, R-012
- **Tests:**
  - `tests/e2e/purchase-order.spec.ts:4` (e2e) — [P0] AC-10 manager mengajukan, admin menyetujui, staff menerima, dan stok bertambah sebesar jumlah PO
- **Justifikasi:** Playwright: create + ajukan (manager), setujui (admin bukan pembuat), terima (staff); current_stock = 12; baris movement memuat nomor PO; fixture produk/supplier sendiri + cleanup.
- **Catatan/Gap:** E2E tidak dijalankan dalam run trace ini (analisis statis).
- **Considered and rejected:** tidak ada

#### PO-P1-01: Item awal hanya produk kritis milik supplier terpilih (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-008
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:47` (component) — [P1] AC-3 offers only the critical products of the selected supplier as initial items
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:127` (component) — [P1] creates a draft from the real item repeater with the critical products at harga_modal
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:213` (component) — [P2] replaces the initial items when the manager switches supplier on the create form
- **Justifikasi:** Produk tidak kritis, supplier lain, tanpa supplier tidak muncul; repeater nyata menyimpan hanya produk kritis; ganti supplier mengganti item.
- **Considered and rejected:** tidak ada

#### PO-P1-02: Harga default item = harga_modal saat draft dibuat; manager dapat mengubah (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-008
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:64` (component) — [P1] AC-4 defaults the item price to harga_modal and lets the manager change it
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:127` (component) — [P1] creates a draft from the real item repeater with the critical products at harga_modal
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:230` (component) — [P2] fills the item price with harga_modal when the manager picks another product of the supplier
- **Justifikasi:** unit_price default 12.500 = harga_modal; diubah ke 11.000 tersimpan, harga_modal produk tetap.
- **Considered and rejected:** tidak ada

#### PO-P1-03: Produk tidak kritis supplier sama boleh ditambah; supplier lain ditolak di domain (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-009
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:130` (api) — [P1] AC-12 only accepts items from the purchase order supplier, critical or not
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:180` (api) — [P1] rejects changing an item to another order or to a product outside the supplier
- **Justifikasi:** Produk supplier lain/tanpa supplier/pindah PO melempar exception; produk tidak kritis supplier sama tersimpan.
- **Considered and rejected:** tidak ada

#### PO-P1-04: Nomor PO-YYYYMM-#### berurutan dalam bulan dan reset ke 0001 di bulan baru (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-007
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:29` (api) — [P1] AC-2 numbers purchase orders PO-YYYYMM-#### sequentially per month as drafts
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:156` (api) — [P1] keeps the purchase order number fixed after creation even for admin
- **Justifikasi:** travelTo Nov (0002) dan Des (0001); nomor tidak bisa diubah setelah dibuat.
- **Catatan/Gap:** Test design merencanakan 2 test; urutan dan reset digabung dalam satu test (bukti tetap lengkap).
- **Considered and rejected:** tidak ada

#### PO-P1-05: Nomor PO unik di DB (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-007
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:206` (api) — [P1] AC-18 enforces a unique purchase order number in the database
- **Justifikasi:** Insert duplikat melempar UniqueConstraintViolationException.
- **Catatan/Gap:** Konkurensi nyata tidak diuji (sesuai test design).
- **Considered and rejected:** tidak ada

#### PO-P1-06: PO tanpa item tidak bisa diajukan (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-005
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:196` (api) — [P1] AC-17 rejects submitting a purchase order without items
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:115` (component) — [P1] notifies the manager and keeps the draft when submitting a purchase order without items
- **Justifikasi:** Domain melempar exception; UI menampilkan notifikasi gagal dan status tetap Draft.
- **Considered and rejected:** tidak ada

#### PO-P1-07: Penolakan wajib catatan; persetujuan boleh tanpa catatan (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-005
- **Tests:**
  - `tests/Feature/PurchaseOrderApprovalTest.php:40` (component) — [P1] AC-3 requires a note when rejecting a purchase order
  - `tests/Feature/PurchaseOrderApprovalTest.php:15` (component) — [P0] AC-1 lets an admin other than the creator approve a submitted purchase order without a note
  - `tests/Feature/PurchaseOrderApprovalTest.php:28` (component) — [P0] AC-2 lets an admin other than the creator reject a submitted purchase order with a note
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:56` (component) — [P1] records the deciding admin, decision time and note and notifies success
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:79` (api) — [P1] requires a rejection note in the domain, not only in the form
- **Justifikasi:** assertHasActionErrors note required; domain menolak catatan kosong/spasi; approve tanpa catatan berhasil.
- **Considered and rejected:** tidak ada

#### PO-P1-08: Staff melihat daftar/detail PO; aksi Terima hanya pada PO disetujui (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-006
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingTest.php:130` (component) — [P1] AC-8 shows the receive action to manager and staff only on approved purchase orders
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:15` (api) — [P0] authorizes receiving by role and status
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:87` (component) — [P1] notifies success and removes the receive action once staff receives the purchase order
  - `tests/Feature/PurchaseOrderApprovalGuardsTest.php:118` (component) — [P2] shows the rejection decision and note to staff on the purchase order detail
  - `tests/Feature/AuthorizationMatrixTest.php:85` (api) — [P0] AC-6 enforces the purchase order authorization matrix
- **Justifikasi:** Receive visible pada approved, hidden pada submitted (UI) dan Policy false untuk draft/ditolak/diterima; staff membuka detail; ViewAny/View staff di matriks.
- **Considered and rejected:** tidak ada

#### PO-P1-09: Regresi: movement tanpa PO tetap mengikuti Policy lama; replay Observer benar (P1)

- **Coverage:** FULL ✅
- **Risiko:** R-003, R-011
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:109` (api) — [P1] still lets users edit and delete movements without a purchase order per the existing policy
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:121` (api) — [P1] replays stock when admin updates and deletes a movement without a purchase order
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:135` (component) — [P1] opens the edit page of a movement without a purchase order for staff
- **Justifikasi:** Policy staff/manager update/delete sesuai matriks lama; update+delete admin me-replay stok 7 -> 5; halaman edit dapat dibuka staff.
- **Considered and rejected:** tidak ada

#### PO-P1-10: E2E staff: halaman daftar dan detail PO tidak merender teks/kolom harga (Harga, Subtotal, Total, Rp) (P1)

- **Coverage:** PARTIAL ⚠️
- **Risiko:** R-004
- **Tests:**
  - `tests/e2e/purchase-order.spec.ts:4` (e2e) — [P0] AC-10 manager mengajukan, admin menyetujui, staff menerima, dan stok bertambah sebesar jumlah PO
- **Justifikasi:** E2E memeriksa halaman detail PO (Harga per Item, Subtotal, Rp = 0 elemen).
- **Catatan/Gap:** Halaman daftar PO (/admin/purchase-orders) tidak diperiksa di E2E dan teks "Total" tidak diassert; kolom total di tabel hanya dibuktikan di level Livewire (CostMaskingTest).
- **Considered and rejected:** tidak ada

#### PO-P2-01: Validasi item: jumlah >= 1, harga >= 0, produk unik per PO (P2)

- **Coverage:** FULL ✅
- **Risiko:** R-009
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:176` (component) — [P2] AC-16 validates item quantity, price, and product uniqueness
- **Justifikasi:** Dataset qty 0, harga -1, produk ganda -> form error dan tidak ada PO tersimpan.
- **Considered and rejected:** tidak ada

#### PO-P2-02: Supplier tidak bisa diganti setelah draft dibuat (P2)

- **Coverage:** FULL ✅
- **Risiko:** R-009
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:165` (api) — [P2] AC-15 keeps the supplier of a purchase order fixed after the draft is created
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:245` (component) — [P2] lets the manager update draft items on the edit page while the supplier stays locked
- **Justifikasi:** Domain melempar exception; field supplier disabled di halaman edit.
- **Considered and rejected:** tidak ada

#### PO-P2-03: Manager dapat menghapus PO draft (P2)

- **Coverage:** FULL ✅
- **Risiko:** R-005
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:153` (component) — [P2] AC-14 lets the manager delete a draft purchase order
- **Justifikasi:** DeleteAction di halaman edit menghapus PO dan item.
- **Considered and rejected:** tidak ada

#### PO-P2-04: Migrasi baru bisa di-rollback bersih (down()) (P2)

- **Coverage:** NONE ❌
- **Risiko:** R-011
- **Tests:** tidak ada
- **Justifikasi:** Tidak ada test rollback migrasi.
- **Catatan/Gap:** Belum ada bukti otomatis bahwa down() migrasi purchase_orders/purchase_order_items/stock_movements.purchase_order_id bersih.
- **Considered and rejected:** tidak ada

#### PO-P3-01: Label status Bahasa Indonesia dan resource di grup navigasi Transaksi (P3)

- **Coverage:** PARTIAL ⚠️
- **Risiko:** -
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftTest.php:21` (component) — [P3] AC-1 shows the purchase order status with its Indonesian label
- **Justifikasi:** Label "Diajukan" diassert untuk status submitted.
- **Catatan/Gap:** Label status lain (draft/disetujui/ditolak/diterima) dan grup navigasi Transaksi tidak diassert.
- **Considered and rejected:** tidak ada

#### PO-P3-02: Harga untuk manager diformat money(IDR) dengan prefix Rp (P3)

- **Coverage:** FULL ✅
- **Risiko:** -
- **Tests:**
  - `tests/Feature/PurchaseOrderDraftGuardsTest.php:264` (component) — [P3] shows item subtotals and the order total in Indonesian rupiah to the manager
  - `tests/Feature/CostMaskingTest.php:50` (component) — [P0] AC-7 hides purchase order item prices and totals from staff but not manager
- **Justifikasi:** Format 37.500,00 / 39.500,00 diassert; manager melihat teks Rp di detail.
- **Considered and rejected:** tidak ada

#### PO-P3-03: Penerimaan PO 50 item sukses; durasi informasional (P3)

- **Coverage:** FULL ✅
- **Risiko:** R-013
- **Tests:**
  - `tests/Feature/PurchaseOrderReceivingGuardsTest.php:172` (api) — [P3] receives a purchase order with fifty items in one transaction
- **Justifikasi:** 50 movement tercipta dan stok tiap produk benar.
- **Catatan/Gap:** Durasi tidak dicatat (ambang UNKNOWN, bukan gate).
- **Considered and rejected:** tidak ada

### Gap Analysis

#### Critical Gaps (BLOCKER) ❌

Tidak ada. Semua 15 kriteria P0 FULL.

#### High Priority Gaps (PR BLOCKER) ⚠️

Tidak ada P1 berstatus NONE. P1 di bawah FULL: **PO-P1-10** (PARTIAL).

- **PO-P1-10** — E2E staff hanya memeriksa halaman detail PO. Rekomendasi: tambah langkah di `tests/e2e/purchase-order.spec.ts` (ID usulan `PO-E2E-002`): *Given* staff login, *When* membuka `/admin/purchase-orders`, *Then* tidak ada header/teks `Total` maupun `Rp`. Bisa menjadi langkah tambahan di test yang sama agar tidak menambah login (throttle 5/menit).

#### Medium Priority Gaps (Nightly) ⚠️

- **PO-P2-04** (NONE) — rollback migrasi PO belum diuji. Rekomendasi: test Feature (`PO-API-P2-04`) yang menjalankan `migrate:rollback --step` untuk tiga migrasi PO lalu `migrate` kembali dan memastikan tabel/kolom hilang dan kembali, atau cek manual saat review (test design mengizinkan keduanya).

#### Low Priority Gaps (Optional) ℹ️

- **PO-P3-01** (PARTIAL) — hanya label `Diajukan` yang diassert; label status lain dan grup navigasi `Transaksi` belum. Dataset label per `PurchaseOrderStatus` cukup.

### Coverage Heuristics Findings

- Endpoint Coverage Gaps: 0 (tidak berlaku)
- Auth/Authz Negative-Path Gaps: 0
- Happy-Path-Only Criteria: 0

### Rejected Evidence

Tidak ada. Setiap test yang dipetakan membuktikan sebagian atau seluruh skenario yang diklaim judulnya. `PurchaseOrderDraftGuardsTest.php:264` (judul menyebut "rupiah") hanya mengassert format angka, tetapi tetap diterima sebagai bukti sebagian PO-P3-02 karena prefix `Rp` dibuktikan oleh `CostMaskingTest.php:50`.

### Quality Assessment

- Seluruh test PO lulus; tidak ada skip/todo; tidak ada komentar di test Pest (sesuai konvensi).
- Aturan bypass-sensitive (R-001, R-003, R-005) diuji dengan `adminUser()` sebagai pelaku — sesuai syarat non-negotiable test design.
- Bukti akses per role memakai `userWithRole('staff'|'manager')` — sesuai `AGENTS.md`.
- **PO-P0-11:** visibilitas tombol edit/hapus per baris tabel `StockMovementResource` tidak diassert langsung; dibuktikan lewat Policy (false), halaman edit forbidden, dan bulk delete yang melewati movement ber-PO. Diterima sebagai FULL; satu `assertActionHidden(TestAction::make('edit')->table($movement))` akan membuatnya eksplisit.
- **Catatan lingkungan:** Pest mencetak `PHP Warning: file_put_contents(vendor/pestphp/pest/.temp/test-results): Permission denied` — cache hasil Pest tidak bisa ditulis user `sail` (kemungkinan direktori milik root). Tidak memengaruhi hasil test.

### Duplicate Coverage Analysis

- **Overlap yang dapat diterima (defense in depth):** keputusan PO (Policy dataset + domain + aksi Livewire), penguncian movement ber-PO (model + Policy + bulk delete), masking harga (Livewire + E2E DOM).
- **Duplikasi tidak perlu:** tidak ditemukan. Aturan bisnis hanya di Domain; E2E satu test alur utama.

### Coverage by Test Level

| Level | Test (unik) | Kriteria tercakup |
| ----- | ----------- | ----------------- |
| E2E (Playwright) | 1 | 3 |
| API/Domain (Pest) | 38 | 21 |
| Component/Livewire (Pest) | 30 | 21 |
| Unit | 0 | 0 |
| Live | 0 | 0 |
| **Total** | **69** (10 berkas) | 31 dari 32 |

### Traceability Recommendations

#### Immediate Actions (Before PR Merge)

Tidak ada blocker.

#### Short-term Actions (This Milestone)

1. Lengkapi **PO-P1-10**: periksa halaman daftar PO sebagai staff di E2E (tanpa `Total`/`Rp`).
2. Jalankan `npm run test:e2e` sekali pada commit ini dan lampirkan hasilnya (E2E tidak dieksekusi dalam run trace).

#### Long-term Actions (Backlog)

1. **PO-P2-04:** bukti rollback migrasi PO.
2. **PO-P3-01:** dataset label status + grup navigasi `Transaksi`.
3. Jalankan `/bmad-testarch-test-review` untuk penilaian kualitas test; `/bmad-testarch-nfr` untuk performa (ambang UNKNOWN, R-013).

---

## PHASE 2: QUALITY GATE DECISION

**Gate Type:** epic · **Decision Mode:** deterministic · **Collection:** `contract_static` / `COLLECTED`

### Evidence Summary

#### Test Execution Results

- Pest (9 berkas terkait PO): **212 passed / 0 failed / 0 skipped**, 566 assertion, 77,5 dtk — dijalankan pada run ini.
- Playwright E2E: tidak dijalankan pada run ini (bukti statis).

#### Coverage Summary (from Phase 1)

- P0: 15/15 (100%) — MET
- P1: 9/10 (90%) — MET (target 90%, minimum 80%)
- Overall: 29/32 (91%) — MET (minimum 80%)

#### Non-Functional Requirements

- Security & data integrity: dibuktikan biner oleh P0 (R-001, R-002, R-003, R-004, R-005, R-006).
- Performance: ambang UNKNOWN — PO-P3-03 hanya informasional; diserahkan ke `/bmad-testarch-nfr`.

#### Flakiness Validation

Tidak diukur (satu kali run). Tidak ada indikasi flaky pada run ini.

### Decision Criteria Evaluation

| Kriteria | Ambang | Aktual | Status |
| -------- | ------ | ------ | ------ |
| P0 coverage | 100% | 100% | ✅ MET |
| P1 coverage | ≥90% (PASS) / ≥80% (min) | 90% | ✅ MET |
| Overall coverage | ≥80% | 91% | ✅ MET |
| Risiko skor 9 (R-003) termitigasi, pelaku admin | wajib | PO-P0-10 FULL dengan `adminUser()` | ✅ |
| Skenario SEC (R-001, R-004, R-006) | 100% | 100% P0 FULL | ✅ |

### GATE DECISION: PASS ✅

### Rationale

P0 coverage is 100%, P1 coverage is 90% (target: 90%), and overall coverage is 91% (minimum: 80%).

Seluruh mitigasi risiko tinggi dari test design punya bukti: R-003 (BLOCK) dibuktikan dengan admin sebagai pelaku pada level model, R-001 dengan admin pembuat, R-002 lewat rollback + penerimaan ulang dari salinan basi, R-004 lewat Livewire dan DOM E2E, R-005 lewat dataset 16 transisi ilegal, R-006 lewat matriks Shield + ability kustom. P1 tepat di ambang 90%; satu-satunya P1 PARTIAL (PO-P1-10) adalah lapisan E2E kedua atas aturan yang sudah dibuktikan di Livewire, sehingga risiko sisa rendah.

### Waiver Register Review

Tidak ada register (`_bmad-output/test-artifacts/gate-waivers.md` tidak ada). Tidak ada waiver yang diajukan atau diterapkan.

### Gate Recommendations (PASS)

1. Lanjutkan ke merge/demo epik purchase-order.
2. Sebelum demo, jalankan `npm run test:e2e` pada app Sail yang sudah di-seed dan pastikan tidak ada residu data (R-012).
3. Tutup PO-P1-10 segera — tanpanya P1 jatuh ke 80–89% (CONCERNS) bila ada satu P1 lain yang mundur.

### Next Steps

- `/bmad-testarch-automate` untuk PO-P1-10, PO-P2-04, PO-P3-01.
- `/bmad-testarch-test-review` dan `/bmad-testarch-nfr`.
- Minta user/fasilitator menjalankan full suite: `docker compose exec -T -u sail laravel.test php artisan test --compact`.

---

## Integrated YAML Snippet (CI/CD)

```yaml
traceability_and_gate:
  target: { type: epic, id: purchase-order }
  source_sha: 402476df7e6e165ea27822c34a794e4850d277f6
  traceability:
    totals: { requirements: 32, full: 29, partial: 2, none: 1, overall_pct: 91 }
    priority: { P0: 100, P1: 90, P2: 75, P3: 67 }
    gaps: { critical: 0, high: 0, medium: 1, low: 0 }
  gate_decision:
    decision: PASS
    p0_status: MET
    p1_status: MET
    overall_status: MET
    tests: { pest_passed: 212, pest_failed: 0, e2e_executed: false }
```

## Related Artifacts

- Oracle: `_bmad-output/test-artifacts/test-design-epic-purchase-order.md`
- SPEC: `_bmad-output/specs/spec-purchase-order/SPEC.md`, `stories.yaml`, `state-machines.md`
- ATDD/Automate: `_bmad-output/test-artifacts/atdd-checklist-*.md`, `automation-summary*.md`
- Machine-readable: `_bmad-output/test-artifacts/e2e-trace-summary.json`, `_bmad-output/test-artifacts/gate-decision.json`

## Asumsi

1. Level "Domain" pada test design dipetakan ke bucket `api`, "Livewire" ke `component` (stack tanpa REST API).
2. Satu `it()` dengan dataset dihitung sebagai satu test case (bukan per baris dataset).
3. E2E dinilai secara statis (isi assertion), tidak dieksekusi pada run ini, karena memakai database dev bersama dan throttle login.
4. Target gate = epic (`gate_type` default workflow `story` dioverride karena permintaan untuk epik).

## Sign-Off

- Gate: **PASS** — Rustan (TEA), 2026-11-08

---

🚨 GATE DECISION: PASS

📊 Coverage Analysis:
- P0 Coverage: 100% (Required: 100%) → MET
- P1 Coverage: 90% (PASS target: 90%, minimum: 80%) → MET
- Overall Coverage: 91% (Minimum: 80%) → MET

✅ Decision Rationale:
P0 coverage is 100%, P1 coverage is 90% (target: 90%), and overall coverage is 91% (minimum: 80%).

⚠️ Critical Gaps: 0 (P0 criteria below FULL coverage)

📝 Recommended Actions:
1. Lengkapi PO-P1-10 (E2E halaman daftar PO untuk staff)
2. Tambah bukti rollback migrasi PO (PO-P2-04)
3. Jalankan /bmad-testarch-test-review

📂 Full Report: _bmad-output/test-artifacts/traceability-matrix.md

✅ GATE: PASS - Release approved, coverage meets standards
