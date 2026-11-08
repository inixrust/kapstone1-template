---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-11-08'
workflowType: 'testarch-atdd'
storyId: '1'
storyKey: 'buat-draft-po-dari-produk-kritis'
storyFile: '_bmad-output/specs/spec-purchase-order/stories.yaml#1'
atddChecklistPath: '_bmad-output/test-artifacts/atdd-checklist-buat-draft-po-dari-produk-kritis.md'
generatedTestFiles:
  - 'tests/Feature/PurchaseOrderDraftTest.php'
  - 'tests/Feature/AuthorizationMatrixTest.php'
  - 'tests/Feature/CostMaskingTest.php'
  - 'tests/Feature/AuditTrailTest.php'
  - 'tests/Pest.php'
inputDocuments:
  - '_bmad-output/specs/spec-purchase-order/stories.yaml'
  - '_bmad-output/specs/spec-purchase-order/SPEC.md'
  - '_bmad-output/specs/spec-purchase-order/state-machines.md'
  - '_bmad-output/specs/spec-purchase-order/brownfield.md'
  - '_bmad-output/test-artifacts/test-design-epic-purchase-order.md'
  - 'AGENTS.md'
  - 'tests/README.md'
  - 'tests/Pest.php'
  - '_bmad/tea/config.yaml'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-quality.md'
acceptanceCriteria:
  - { id: 'AC-1', idSource: 'generated', text: 'Resource Purchase Order (grup Transaksi) menampilkan status dengan label Bahasa Indonesia' }
  - { id: 'AC-2', idSource: 'generated', text: 'Draft baru bernomor PO-YYYYMM-#### berurutan per bulan, berstatus draft, created_by = pembuat' }
  - { id: 'AC-3', idSource: 'generated', text: 'Item awal hanya produk kritis milik supplier terpilih' }
  - { id: 'AC-4', idSource: 'generated', text: 'Harga default item = harga_modal produk dan dapat diubah manager' }
  - { id: 'AC-5', idSource: 'generated', text: 'Manager mengajukan draft (draft → diajukan)' }
  - { id: 'AC-6', idSource: 'generated', text: 'Permission Shield PurchaseOrder lewat RoleSeeder sesuai matriks otorisasi' }
  - { id: 'AC-7', idSource: 'generated', text: 'Harga item, subtotal, dan total tersembunyi total untuk staff' }
  - { id: 'AC-8', idSource: 'generated', text: 'Perubahan status tercatat di audit trail dengan causer' }
  - { id: 'AC-9', idSource: 'generated', text: 'Staff tidak bisa membuat atau mengajukan PO' }
  - { id: 'AC-10', idSource: 'generated', text: 'Transisi ajukan dari status selain draft ditolak di domain, termasuk admin' }
  - { id: 'AC-11', idSource: 'generated', text: 'PO non-draft tidak dapat diubah item/harganya atau dihapus, termasuk admin' }
  - { id: 'AC-12', idSource: 'generated', text: 'Item hanya produk milik supplier PO; produk tidak kritis supplier itu boleh ditambahkan' }
  - { id: 'AC-13', idSource: 'generated', text: 'Audit PO tidak memuat harga (hanya status)' }
  - { id: 'AC-14', idSource: 'generated', text: 'Manager dapat menghapus PO draft' }
  - { id: 'AC-15', idSource: 'generated', text: 'Supplier PO tidak bisa diganti setelah draft dibuat' }
  - { id: 'AC-16', idSource: 'generated', text: 'Validasi item: jumlah ≥ 1, harga ≥ 0, produk unik per PO' }
  - { id: 'AC-17', idSource: 'generated', text: 'PO tanpa item tidak bisa diajukan' }
  - { id: 'AC-18', idSource: 'generated', text: 'Nomor PO unik di database' }
---

# ATDD Checklist - Epic purchase-order, Story 1: Buat draft PO dari produk kritis

**Date:** 2026-11-08
**Author:** Rustan
**Primary Test Level:** Pest Feature — Domain (model) + Livewire (halaman Filament)

---

## Story Summary

CAP-1: manager memilih satu supplier, sistem menawarkan produk kritis supplier itu sebagai item awal dengan harga default `harga_modal`, manager mengisi jumlah (boleh mengubah harga), lalu mengajukan PO. Termasuk permission Shield + `RoleSeeder`, masking harga untuk staff, dan audit perubahan status.

**As a** manager
**I want** membuat draft PO dari produk kritis supplier lalu mengajukannya
**So that** pesanan ulang tercatat di aplikasi dan siap disetujui admin

---

## Acceptance Criteria

Story tidak punya id AC; registry di bawah diturunkan berurutan dari deskripsi story → CAP-1 success → Constraints → Assumptions SPEC (semua `generated`).

| ID | Sumber | Kriteria | Prioritas | Skenario test design |
| --- | --- | --- | --- | --- |
| AC-1 | deskripsi | Resource PO (grup Transaksi), label status Indonesia | P3 | PO-P3-01 |
| AC-2 | deskripsi | Nomor `PO-YYYYMM-####` urut per bulan, status draft | P1 | PO-P1-04 |
| AC-3 | deskripsi | Item awal = produk kritis supplier terpilih saja | P1 | PO-P1-01 |
| AC-4 | deskripsi | Harga default = `harga_modal`, bisa diubah | P1 | PO-P1-02 |
| AC-5 | deskripsi | Ajukan: draft → diajukan | P0 | (alur inti, prasyarat PO-P0-14) |
| AC-6 | deskripsi | Matriks otorisasi `PurchaseOrder` | P0 | PO-P0-03 |
| AC-7 | deskripsi | Harga tersembunyi total untuk staff | P0 | PO-P0-12 |
| AC-8 | deskripsi | Audit transisi ajukan + causer | P0 | PO-P0-14 (ajukan) |
| AC-9 | CAP-1 success | Staff tidak bisa membuat/mengajukan | P0 | PO-P0-04 |
| AC-10 | Constraints | Ajukan non-draft ditolak domain, juga admin | P0 | PO-P0-05 (bagian draft) |
| AC-11 | Constraints/Assumptions | PO non-draft terkunci (item/harga/hapus), juga admin | P0 | PO-P0-06 |
| AC-12 | Constraints | Item hanya produk supplier PO; tidak kritis boleh | P1 | PO-P1-03 |
| AC-13 | Constraints | Activity PO tanpa harga | P0 | PO-P0-13 |
| AC-14 | Assumptions | Manager menghapus draft | P2 | PO-P2-03 |
| AC-15 | Assumptions | Supplier tetap setelah draft | P2 | PO-P2-02 |
| AC-16 | Assumptions | Validasi jumlah/harga/produk unik | P2 | PO-P2-01 |
| AC-17 | Assumptions | Tanpa item tidak bisa diajukan | P1 | PO-P1-06 |
| AC-18 | Assumptions | Nomor unik di DB | P1 | PO-P1-05 |

---

## Story Integration Metadata

- **Story ID:** `1`
- **Story Key:** `buat-draft-po-dari-produk-kritis` (slug judul; story berasal dari `stories.yaml`, bukan berkas BMM)
- **Story File:** `_bmad-output/specs/spec-purchase-order/stories.yaml` (entri `id: "1"`)
- **Checklist Path:** `_bmad-output/test-artifacts/atdd-checklist-buat-draft-po-dari-produk-kritis.md`

---

## Keputusan workflow

- **Stack:** `fullstack` (Laravel + Vite/Playwright). Level "API" diganti Pest Feature Domain/Livewire karena aplikasi tanpa REST API (sesuai test design).
- **Mode generasi:** AI generation; recording browser tidak dipakai (halaman PO belum ada).
- **Mode eksekusi:** sequential (tanpa subagent).
- **Penanda red phase:** padanan Pest untuk `test.skip()` adalah `->skip('ATDD red phase: story 1 purchase order belum diimplementasikan')` pada setiap leaf; suite tetap hijau sampai dev mengaktifkannya.
- **E2E:** tidak ada untuk story 1. Test design menaruh kedua E2E PO (PO-P0-15, PO-P1-10) di story 3; render DOM harga untuk staff di story 1 dibuktikan di Livewire (AC-7).
- **Playwright Utils / Pact:** tidak berlaku (scaffold Pest; `@seontechnologies/*` tidak terpasang; tidak ada kontrak API).

---

## Red-Phase Test Scaffolds Created

18 leaf (satu per AC), 36 kasus setelah dataset diekspansi. Judul tiap leaf memuat tepat satu `[P#] AC-<n>`.

### `tests/Feature/PurchaseOrderDraftTest.php` (baru, 14 leaf)

| Test | Level | Gagal saat diaktifkan karena |
| --- | --- | --- |
| `[P3] AC-1 shows the purchase order status with its Indonesian label` | Livewire | Model `PurchaseOrder` belum ada |
| `[P1] AC-2 numbers purchase orders PO-YYYYMM-#### sequentially per month as drafts` | Domain | Model belum ada |
| `[P1] AC-3 offers only the critical products of the selected supplier as initial items` | Livewire | Komponen `CreatePurchaseOrder` belum ada |
| `[P1] AC-4 defaults the item price to harga_modal and lets the manager change it` | Livewire | Komponen `CreatePurchaseOrder` belum ada |
| `[P0] AC-5 lets the manager submit a draft purchase order` | Livewire | Model belum ada |
| `[P0] AC-9 forbids staff from creating or submitting a purchase order` | Livewire | Model belum ada |
| `[P0] AC-10 rejects submitting a non-draft purchase order even for admin` (×4 status) | Domain | Model belum ada |
| `[P0] AC-11 locks items and deletion of a non-draft purchase order even for admin` (×4 status) | Domain | Model belum ada |
| `[P1] AC-12 only accepts items from the purchase order supplier, critical or not` | Domain | Model belum ada |
| `[P2] AC-14 lets the manager delete a draft purchase order` | Livewire | Model belum ada |
| `[P2] AC-15 keeps the supplier of a purchase order fixed after the draft is created` | Domain | Model belum ada |
| `[P2] AC-16 validates item quantity, price, and product uniqueness` (×3) | Livewire | Komponen `CreatePurchaseOrder` belum ada |
| `[P1] AC-17 rejects submitting a purchase order without items` | Domain | Model belum ada |
| `[P1] AC-18 enforces a unique purchase order number in the database` | Domain | Model belum ada |

### Perluasan berkas yang ada

| Berkas | Test | Level |
| --- | --- | --- |
| `tests/Feature/AuthorizationMatrixTest.php` | `[P0] AC-6 enforces the purchase order authorization matrix` (dataset 11 baris, `userWithRole('manager'|'staff')`) | Domain |
| `tests/Feature/CostMaskingTest.php` | `[P0] AC-7 hides purchase order item prices and totals from staff but not manager` | Livewire |
| `tests/Feature/AuditTrailTest.php` | `[P0] AC-8 logs the submit transition with the causer and old and new status` | Domain |
| `tests/Feature/AuditTrailTest.php` | `[P0] AC-13 records only the status in purchase order activity, never prices` | Domain |

### Helper bersama (`tests/Pest.php`)

- `productWithStockFor(?Supplier, int $stock, array $attributes = [])` — produk `reorder_point = 10`, stok dibentuk lewat movement IN (≤ 10 = kritis).
- `purchaseOrderWithItem(string $status = 'draft', array $attributes = [])` — PO + satu item produk kritis milik supplier PO; status non-draft dipasang lewat query builder agar setup tidak terhalang penjagaan transisi.

Kedua helper baru bisa dipanggil setelah model + factory PO ada; test lama tidak memanggilnya.

---

## Kontrak implementasi yang diasumsikan scaffold

Diambil opsi paling sederhana yang konsisten dengan kode yang ada (dicatat sebagai asumsi; bila dev memilih nama lain, sesuaikan test, bukan sebaliknya diam-diam):

1. **Enum** `App\Enums\PurchaseOrderStatus` (`HasLabel`, `HasColor` seperti `StockMovementType`): `Draft='draft'`, `Submitted='submitted'`, `Approved='approved'`, `Rejected='rejected'`, `Received='received'`; label `Draft`/`Diajukan`/`Disetujui`/`Ditolak`/`Diterima`.
2. **Model** `App\Models\PurchaseOrder`: kolom `number` (unique), `supplier_id`, `status` (cast enum), `created_by`; relasi `supplier()`, `items()`; method domain `submit(): void`. Event `creating` mengisi `number` (hanya bila kosong), `status = draft`, `created_by = Auth::id()`.
3. **Model** `App\Models\PurchaseOrderItem`: `purchase_order_id`, `product_id`, `qty`, `unit_price` (decimal); unique (`purchase_order_id`, `product_id`); FK ke PO `cascadeOnDelete`.
4. **Penjagaan domain** melempar `Illuminate\Auth\Access\AuthorizationException` (preseden `StockMovementObserver::creating()`): ajukan non-draft, ajukan tanpa item, ubah/hapus item atau hapus PO non-draft, ganti supplier, item dari supplier lain. Berlaku juga untuk admin.
5. **Activity** PO: `LogsActivity`, `useLogName('purchase_order')`, `logOnly(['status'])`, `logOnlyDirty()`.
6. **Filament** `App\Filament\Resources\PurchaseOrders\PurchaseOrderResource` (grup `Transaksi`) dengan halaman `ListPurchaseOrders`, `CreatePurchaseOrder`, `EditPurchaseOrder` (header `DeleteAction`), `ViewPurchaseOrder` (aksi `submit`). Form: select `supplier_id` yang mengisi Repeater `items` (relationship; field `product_id`, `qty`, `unit_price`) dengan produk kritis supplier saat dipilih; `product_id` `distinct()`, `qty` `minValue(1)`, `unit_price` `minValue(0)`. Kolom tabel `status` dan `total`; harga di halaman view diformat `money('IDR')`.
7. **Policy** `PurchaseOrderPolicy` mengikuti pola Shield + ability kustom `submit` (manager/admin, PO draft). Staff hanya `ViewAny`/`View`.

---

## Required data factories & seeder (kebutuhan fixture)

- [ ] `PurchaseOrderFactory` (default draft, `supplier_id => Supplier::factory()`, `created_by => User::factory()`; state `submitted`/`approved`/`rejected`/`received` opsional)
- [ ] `PurchaseOrderItemFactory` (`qty` ≥ 1, `unit_price` dari `harga_modal` produk)
- [ ] `RoleSeeder`: `PurchaseOrder` masuk `DOMAIN_RESOURCES` (manager) dan staff hanya `STAFF_READ_ACTIONS`
- [ ] Migrasi baru `purchase_orders` + `purchase_order_items` (dengan `down()`)

---

## Implementation Checklist (dev, story 1)

Aktifkan satu test per tugas dengan menghapus `->skip(...)`, lalu jalankan `docker compose exec -T -u sail laravel.test php artisan test --compact --filter='AC-<n>'` (atau `--filter=PurchaseOrder`).

- [ ] Migrasi, enum, model, factory → aktifkan AC-2, AC-18
- [ ] `RoleSeeder` + Policy → aktifkan AC-6
- [ ] Penjagaan domain → aktifkan AC-10, AC-11, AC-12, AC-15, AC-17
- [ ] Activity log → aktifkan AC-8, AC-13
- [ ] Resource Filament (form, tabel, view, aksi submit) → aktifkan AC-1, AC-3, AC-4, AC-5, AC-7, AC-9, AC-14, AC-16
- [ ] Jalankan `ui-consistency:finding-patterns` sebelum membangun halaman Filament (instruksi story)
- [ ] `vendor/bin/pint --dirty --format agent`; minta user menjalankan full suite

---

## Cabang sekunder untuk green phase (`/bmad-testarch-automate`)

- AC-2: dua draft simultan (konkurensi; ambang UNKNOWN, cukup unique index + review `lockForUpdate`).
- AC-5: aksi `submit` disembunyikan pada PO non-draft.
- AC-7: entri harga di halaman edit/relation manager (bila dev memakai relation manager, bukan Repeater) dan PO-P3-02 format `Rp`.
- AC-8/AC-13: transisi setujui/tolak/terima — story 2 dan 3.
- AC-9: panggilan langsung `submit` oleh staff lewat Livewire (`callAction` ditolak), bukan hanya disembunyikan.
- AC-10: transisi lain (draft → disetujui/diterima) — story 2 dan 3 saat method-nya ada.
- AC-14: manager tidak bisa menghapus PO non-draft lewat UI (aksi tersembunyi).

---

## Validasi (step 5)

- `php artisan test --compact` empat berkas terdampak: **41 passed, 36 skipped** — test lama tetap hijau, semua scaffold ter-skip.
- Aktivasi sementara `PurchaseOrderDraftTest` (salinan tanpa `->skip`, lalu dihapus): **22/22 gagal** — 17× `Class "App\Models\PurchaseOrder" not found`, 5× `Unable to find component: CreatePurchaseOrder`. Tidak ada kegagalan karena setup salah.
- Pint: passed.
- Catatan lingkungan: Pest mencetak warning `file_put_contents(vendor/pestphp/pest/.temp/test-results): Permission denied` (berkas cache milik root di `vendor/`, sudah ada sebelumnya; tidak memengaruhi hasil).

---

## Asumsi dry run

- Story key dibuat dari slug judul karena story berasal dari `stories.yaml`.
- Satu leaf per AC; ability `submit` per role dibuktikan lewat AC-5 (manager) dan AC-9 (staff), bukan leaf matriks terpisah.
- Penjagaan domain memakai `AuthorizationException` untuk semua pelanggaran (asumsi 2 test design).
- Tidak ada perubahan kode aplikasi; hanya test, helper test, dan checklist ini. Tidak ada commit (fasilitator yang meng-commit).
