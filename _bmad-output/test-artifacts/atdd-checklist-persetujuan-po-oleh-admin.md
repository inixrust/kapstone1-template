---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-11-08'
workflowType: 'testarch-atdd'
storyId: '2'
storyKey: 'persetujuan-po-oleh-admin'
storyFile: '_bmad-output/specs/spec-purchase-order/stories.yaml#2'
atddChecklistPath: '_bmad-output/test-artifacts/atdd-checklist-persetujuan-po-oleh-admin.md'
generatedTestFiles:
  - 'tests/Feature/PurchaseOrderApprovalTest.php'
  - 'tests/Pest.php'
inputDocuments:
  - '_bmad-output/specs/spec-purchase-order/stories.yaml'
  - '_bmad-output/specs/spec-purchase-order/SPEC.md'
  - '_bmad-output/specs/spec-purchase-order/state-machines.md'
  - '_bmad-output/specs/spec-purchase-order/brownfield.md'
  - '_bmad-output/test-artifacts/test-design-epic-purchase-order.md'
  - '_bmad-output/test-artifacts/atdd-checklist-buat-draft-po-dari-produk-kritis.md'
  - 'AGENTS.md'
  - 'tests/Pest.php'
  - 'tests/Feature/PurchaseOrderDraftTest.php'
  - 'tests/Feature/AuditTrailTest.php'
  - 'app/Models/PurchaseOrder.php'
  - 'app/Observers/PurchaseOrderObserver.php'
  - 'app/Policies/PurchaseOrderPolicy.php'
  - 'app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php'
  - '_bmad/tea/config.yaml'
acceptanceCriteria:
  - { id: 'AC-1', idSource: 'generated', text: 'Admin selain pembuat menyetujui PO diajukan → disetujui; catatan opsional' }
  - { id: 'AC-2', idSource: 'generated', text: 'Admin selain pembuat menolak PO diajukan dengan catatan → ditolak; catatan tersimpan' }
  - { id: 'AC-3', idSource: 'generated', text: 'Catatan wajib saat menolak' }
  - { id: 'AC-4', idSource: 'generated', text: 'Pembuat PO (termasuk admin pembuat) tidak dapat menyetujui/menolak PO sendiri; ditegakkan di domain' }
  - { id: 'AC-5', idSource: 'generated', text: 'Manager dan staff tidak bisa memutuskan PO (hanya admin)' }
  - { id: 'AC-6', idSource: 'generated', text: 'Keputusan atas PO yang bukan diajukan ditolak di domain, termasuk admin' }
  - { id: 'AC-7', idSource: 'generated', text: 'Transisi setujui/tolak tercatat di audit trail dengan causer = admin pemutus' }
---

# ATDD Checklist - Epic purchase-order, Story 2: Persetujuan PO oleh admin

**Date:** 2026-11-08
**Author:** Rustan
**Primary Test Level:** Pest Feature — Domain (model) + Livewire (halaman view Filament)

---

## Story Summary

CAP-2: admin menyetujui atau menolak PO berstatus diajukan, catatan wajib saat menolak, dan pembuat PO tidak boleh memutuskan PO buatannya sendiri — ditegakkan di domain, bukan hanya Policy, karena admin melewati semua Policy (Shield `intercept_gate = before`).

**As an** admin
**I want** menyetujui atau menolak PO yang diajukan orang lain, dengan catatan
**So that** setiap pesanan ulang disetujui oleh orang yang berbeda dari pembuatnya

---

## Acceptance Criteria

Story tidak punya id AC; registry diturunkan berurutan dari deskripsi story → CAP-2 success → Constraints/Assumptions SPEC (semua `generated`). Prioritas dari `test-design-epic-purchase-order.md`.

| ID | Sumber | Kriteria | Prioritas | Skenario test design | Risiko |
| --- | --- | --- | --- | --- | --- |
| AC-1 | deskripsi + CAP-2 success | Admin lain menyetujui → disetujui, catatan opsional | P0 | alur inti (prasyarat PO-P0-07, PO-P0-15), PO-P1-07 (opsional) | R-001 |
| AC-2 | deskripsi + CAP-2 success | Admin lain menolak dengan catatan → ditolak, catatan tersimpan | P0 | alur inti | R-005 |
| AC-3 | deskripsi + Assumptions | Catatan wajib saat menolak | P1 | PO-P1-07 | R-005 |
| AC-4 | deskripsi + Constraints | Pembuat (termasuk admin) tidak bisa setuju/tolak PO sendiri | P0 | PO-P0-01 | R-001 |
| AC-5 | CAP-2 success + Constraints | Manager dan staff tidak bisa memutuskan | P0 | PO-P0-02 | R-001, R-006 |
| AC-6 | CAP-2 success + state-machines | Keputusan atas PO non-diajukan ditolak, juga admin | P0 | PO-P0-05 (bagian keputusan) | R-005 |
| AC-7 | Constraints | Audit transisi setujui/tolak + causer | P0 | PO-P0-14 (setujui/tolak) | R-010 |

---

## Story Integration Metadata

- **Story ID:** `2`
- **Story Key:** `persetujuan-po-oleh-admin` (slug judul; story berasal dari `stories.yaml`, belum ada berkas story BMM)
- **Story File:** `_bmad-output/specs/spec-purchase-order/stories.yaml` (entri `id: "2"`)
- **Checklist Path:** `_bmad-output/test-artifacts/atdd-checklist-persetujuan-po-oleh-admin.md`
- **Dependensi:** story 1 (model, enum, Policy, `ViewPurchaseOrder`, factory) — sudah selesai (`77c6a7d`).

---

## Keputusan workflow

- **Stack:** `fullstack` (Laravel + Vite/Playwright). Level "API" diganti Pest Feature Domain/Livewire karena aplikasi tanpa REST API (sesuai test design).
- **Mode generasi:** AI generation; recording browser tidak dipakai (aksi setujui/tolak belum ada).
- **Mode eksekusi:** sequential (tanpa subagent).
- **Penanda red phase:** padanan Pest untuk `test.skip()` adalah `->skip('ATDD red phase: story 2 persetujuan purchase order belum diimplementasikan')` pada setiap leaf.
- **E2E:** tidak ada untuk story 2. Test design menaruh E2E PO (PO-P0-15 manager → admin → staff, PO-P1-10) di story 3.
- **Playwright Utils / Pact:** tidak berlaku (scaffold Pest; tidak ada kontrak API).
- **Knowledge fragments:** core (`data-factories`, `component-tdd`, `test-quality`, `test-healing-patterns`) + backend (`test-levels-framework`, `test-priorities-matrix`, `ci-burn-in`); fragmen Playwright tidak relevan karena tidak ada scaffold E2E.

---

## Red-Phase Test Scaffolds Created

7 leaf (satu per AC), 19 kasus setelah dataset diekspansi. Judul tiap leaf memuat tepat satu `[P#] AC-<n>`.

### `tests/Feature/PurchaseOrderApprovalTest.php` (baru)

| Test | Level | Kasus | Assertion pertama yang gagal |
| --- | --- | --- | --- |
| `[P0] AC-1 lets an admin other than the creator approve a submitted purchase order without a note` | Livewire | 1 | aksi `approve` belum ada → status ≠ `Approved` |
| `[P0] AC-2 lets an admin other than the creator reject a submitted purchase order with a note` | Livewire | 1 | aksi `reject` belum ada → status ≠ `Rejected` |
| `[P1] AC-3 requires a note when rejecting a purchase order` | Livewire | 1 | `assertHasActionErrors(['note' => 'required'])` |
| `[P0] AC-4 forbids the creator from deciding their own purchase order even when the creator is admin` | Domain | 2 (approve/reject) | `toThrow(AuthorizationException)` — kini `BadMethodCallException` |
| `[P0] AC-5 forbids manager and staff from deciding a purchase order` | Livewire + Domain | 4 (role × keputusan) | domain `toThrow(AuthorizationException)` |
| `[P0] AC-6 rejects deciding a purchase order that is not submitted even for admin` | Domain | 8 (4 status × keputusan) | `toThrow(AuthorizationException)` |
| `[P0] AC-7 logs the approve and reject transitions with the deciding admin as causer` | Domain | 2 | `properties.attributes.status` = `approved`/`rejected` |

AC-4 dan AC-6 memakai `adminUser()` sebagai pelaku dengan sengaja: Policy tidak membuktikan apa pun untuk admin (bypass Shield), jadi hanya penjagaan domain yang bisa membuat test ini hijau. AC-5 memakai `userWithRole('manager'|'staff')`.

### Helper bersama (`tests/Pest.php`)

- `decidePurchaseOrder(PurchaseOrder $purchaseOrder, string $decision)` — memanggil `approve()` atau `reject('Catatan penolakan')`, agar dataset keputusan memakai satu jalur. Test lama tidak memanggilnya.
- Dipakai ulang dari story 1: `purchaseOrderWithItem()`, `userWithRole()`, `adminUser()`.

---

## Kontrak implementasi yang diasumsikan scaffold

Opsi paling sederhana yang konsisten dengan kode story 1 (dicatat sebagai asumsi; bila dev memilih nama lain, sesuaikan test secara eksplisit):

1. **Method domain** di `App\Models\PurchaseOrder`, mengikuti pola `submit()`:
   - `approve(?string $note = null): void` → status `Approved`.
   - `reject(string $note): void` → status `Rejected`.
   - Keduanya melempar `Illuminate\Auth\Access\AuthorizationException` bila: PO bukan `Submitted`; tidak ada user login; user login = `created_by` (berlaku juga untuk admin); user `cannot('approve'|'reject', $this)`.
   - Urutan cek bebas, tetapi cek pembuat **tidak boleh** bergantung pada Policy.
   - Status disimpan lewat `save()` model agar `PurchaseOrderObserver::updating()` (state machine) dan activitylog tetap berlaku; tidak ada activity bila keputusan ditolak.
2. **Migrasi baru** (bukan mengubah `2026_11_08_053208_create_purchase_orders_table`), dengan `down()`: kolom `decided_by` (FK `users`, nullable, `nullOnDelete`), `decided_at` (timestamp nullable), `decision_note` (text nullable). Kolom diisi method domain, tidak masuk `$fillable`.
3. **Policy** `PurchaseOrderPolicy`: ability `approve` dan `reject` bernilai `false` untuk manager dan staff (hanya admin lewat bypass Shield, atau permission kustom yang hanya dimiliki admin). Tidak ada perubahan permission manager/staff di `RoleSeeder`.
4. **Filament** `ViewPurchaseOrder`: header action `approve` (label `Setujui`, Textarea `note` opsional) dan `reject` (label `Tolak`, Textarea `note` `required()`), `->authorize(...)`, terlihat hanya untuk PO `Submitted` dan bukan untuk pembuatnya; `AuthorizationException` dari domain ditangkap menjadi notifikasi danger seperti aksi `submit`.
5. **Activity** tetap `logOnly(['status'])`: catatan keputusan tidak perlu masuk properties (cukup kolom `decision_note`).
6. **Tidak ada notifikasi** email/database ke pembuat (non-goal SPEC).

---

## Required data factories & seeder

- [ ] Tidak ada factory baru wajib: setup memakai `purchaseOrderWithItem($status, ['created_by' => ...])` (status dipasang lewat query builder).
- [ ] Opsional: state factory `submitted`/`approved` di `PurchaseOrderFactory` untuk automate.
- [ ] Migrasi baru kolom keputusan (lihat kontrak #2).

---

## Implementation Checklist (dev, story 2)

Aktifkan satu test per tugas dengan menghapus `->skip(...)`, lalu jalankan
`docker compose exec -T -u sail laravel.test php artisan test --compact tests/Feature/PurchaseOrderApprovalTest.php --filter='AC-<n> '`.

- [ ] Migrasi kolom keputusan → prasyarat AC-1, AC-2
- [ ] `approve()`/`reject()` + penjagaan status → aktifkan AC-6
- [ ] Penjagaan pembuat di domain → aktifkan AC-4
- [ ] Ability Policy `approve`/`reject` → aktifkan AC-5 (bagian domain)
- [ ] Activity transisi → aktifkan AC-7
- [ ] Aksi Filament `Setujui`/`Tolak` di `ViewPurchaseOrder` → aktifkan AC-1, AC-2, AC-3, AC-5 (bagian UI)
- [ ] Jalankan `ui-consistency:adjusting` → `implementing` → `verifying` untuk perubahan halaman view (aksi header baru)
- [ ] `vendor/bin/pint --dirty --format agent`; minta user menjalankan full suite

---

## Cabang sekunder untuk green phase (`/bmad-testarch-automate`)

- AC-1: `decision_note` tersimpan bila admin mengisi catatan saat menyetujui; `decided_at` terisi.
- AC-3: `reject('')`/spasi langsung di domain ditolak (catatan wajib juga di domain, bukan hanya form).
- AC-4: aksi `approve`/`reject` **tersembunyi** di UI untuk admin pembuat (mitigasi R-001 di test design); panggilan Livewire langsung (`callAction`) oleh pembuat tidak mengubah status.
- AC-5: `assertActionHidden` pada aksi yang belum ada mungkin sudah lolos di red phase; bukti red AC-5 ada di panggilan domain. Tambahkan juga `expect($user->can('approve', $po))->toBeFalse()` per role bila perlu baris matriks terpisah.
- AC-6: aksi `Setujui`/`Tolak` tersembunyi pada PO non-diajukan.
- AC-7: activity keputusan tidak memuat harga (perluas AC-13 story 1 ke transisi setujui/tolak).
- Staff melihat status `Ditolak` + catatan penolakan di halaman detail (bila dev menampilkan catatan di infolist).

---

## Validasi (step 5)

- `php artisan test --compact` berkas PO + audit (`PurchaseOrderApprovalTest`, `PurchaseOrderDraftTest`, `PurchaseOrderDraftGuardsTest`, `AuditTrailTest`): **76 passed, 19 skipped** — test lama tetap hijau, semua scaffold ter-skip.
- Aktivasi sementara (salinan tanpa `->skip`, lalu dihapus): **19/19 gagal** karena fitur belum ada — `Call to undefined method PurchaseOrder::approve()/reject()` dan `BadMethodCallException` bukannya `AuthorizationException`; AC-1..3 gagal karena aksi Filament `approve`/`reject` belum ada. Tidak ada kegagalan karena setup salah.
- Pint: passed.
- Setiap leaf memuat tepat satu id AC dari registry; 7 AC → 7 leaf.
- Catatan lingkungan: Pest mencetak warning `file_put_contents(vendor/pestphp/pest/.temp/test-results): Permission denied` (berkas cache milik root di `vendor/`, sudah ada sejak story 1; tidak memengaruhi hasil).

---

## Asumsi dry run

- Story key dibuat dari slug judul karena story berasal dari `stories.yaml`.
- Kolom keputusan bernama `decided_by`/`decided_at`/`decision_note` lewat migrasi baru (SPEC menyebut "disertai catatan" tanpa nama kolom).
- Penjagaan domain memakai `AuthorizationException` untuk semua pelanggaran, konsisten dengan `submit()` story 1.
- Larangan memutuskan sendiri berlaku untuk setuju dan tolak (Assumptions SPEC).
- Tidak ada perubahan kode aplikasi; hanya test, helper test, dan checklist ini. Tidak ada commit (fasilitator yang meng-commit).

## Next recommended workflow

`bmad-build` / dev story 2 (aktifkan scaffold satu per satu), lalu `/bmad-testarch-automate` untuk cabang sekunder.
