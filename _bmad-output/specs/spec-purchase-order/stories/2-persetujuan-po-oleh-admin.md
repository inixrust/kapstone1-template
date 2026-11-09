---
title: 'Story 2 — Persetujuan PO oleh admin'
type: 'feature'
created: '2026-11-08'
status: 'done'
baseline_commit: '94a8c86f61c8bbe6e02e6fdac8da7598465bd43e'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-purchase-order/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-purchase-order/state-machines.md'
  - '{project-root}/_bmad-output/specs/spec-purchase-order/brownfield.md'
  - '{project-root}/_bmad-output/test-artifacts/atdd-checklist-persetujuan-po-oleh-admin.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** PO yang diajukan belum bisa diputuskan di aplikasi, sehingga tidak ada persetujuan oleh orang yang berbeda dari pembuatnya.

**Approach:** Method domain `PurchaseOrder::approve(?string $note)` dan `reject(string $note)` (diajukan → disetujui | ditolak) yang menegakkan status, pelaku login, larangan pembuat, dan ability Policy; aksi header `Setujui`/`Tolak` di halaman detail PO memanggil method itu.

## Boundaries & Constraints

**Always:** Hanya admin yang boleh memutuskan (Policy `approve`/`reject` = false untuk role lain; admin lolos lewat bypass Shield). Larangan pembuat memutuskan PO sendiri dicek di domain tanpa bergantung Policy (berlaku juga untuk admin). Keputusan hanya atas PO `Submitted`. Catatan wajib saat menolak (form dan domain), opsional saat menyetujui. Keputusan disimpan di kolom baru `decided_by`, `decided_at`, `decision_note` (migrasi baru dengan `down()`, tidak fillable). Status disimpan lewat `save()` agar Observer dan activitylog (`purchase_order`, causer = admin) berlaku; keputusan yang ditolak tidak membuat activity. Pelanggaran melempar `AuthorizationException`; UI menampilkan notifikasi danger. Label Bahasa Indonesia. Ekspektasi test ATDD tidak diubah; hanya `->skip()` yang dihapus.

**Never:** Mengubah migrasi lama; notifikasi; perubahan permission di `RoleSeeder`; aksi terima (story 3).

**Asumsi (dry run):** aksi tersembunyi untuk pembuat PO dan untuk PO non-diajukan; infolist menampilkan "Diputuskan oleh", "Diputuskan", "Catatan keputusan" (terlihat juga oleh staff, tanpa harga); `decided_by` FK `users` `nullOnDelete`.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected | Error Handling |
|---|---|---|---|
| Setujui | admin ≠ pembuat, PO diajukan, catatan kosong | disetujui, `decided_by` = admin | — |
| Tolak | admin ≠ pembuat, catatan diisi | ditolak, `decision_note` tersimpan | — |
| Tolak tanpa catatan | catatan kosong | status tetap | error form `note` required; domain `AuthorizationException` |
| Pembuat memutuskan | admin pembuat | status tetap, tanpa activity | `AuthorizationException` |
| Bukan admin | manager/staff | aksi tersembunyi, status tetap | `AuthorizationException` |
| Status salah | draft/disetujui/ditolak/diterima | status tetap | `AuthorizationException` |

</frozen-after-approval>

## Code Map

- `app/Models/PurchaseOrder.php` -- tambah `approve()`/`reject()` mengikuti pola `submit()`, relasi `decider()`, cast `decided_at`.
- `app/Observers/PurchaseOrderObserver.php` -- `updating()` sudah menegakkan state machine; tidak diubah.
- `app/Enums/PurchaseOrderStatus.php` -- `canTransitionTo()` sudah mengizinkan Submitted → Approved/Rejected.
- `app/Policies/PurchaseOrderPolicy.php` -- tambah ability `approve`/`reject` (false; admin lewat bypass).
- `app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php` -- pola aksi `submit` (authorize, visible, try/catch notifikasi).
- `app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderInfolist.php` -- entri keputusan.
- `tests/Feature/PurchaseOrderApprovalTest.php`, `tests/Pest.php` (`decidePurchaseOrder()`) -- scaffold ATDD.

## Tasks & Acceptance

**Execution:**
- [x] `database/migrations/*_add_decision_columns_to_purchase_orders_table.php` -- kolom keputusan + `down()`.
- [x] `app/Models/PurchaseOrder.php` -- `approve()`, `reject()`, `decider()`, cast.
- [x] `app/Policies/PurchaseOrderPolicy.php` -- `approve`, `reject`.
- [x] `app/Filament/Resources/PurchaseOrders/Pages/ViewPurchaseOrder.php` -- aksi `Setujui`/`Tolak`.
- [x] `app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderInfolist.php` -- entri keputusan.
- [x] `tests/Feature/PurchaseOrderApprovalTest.php` -- hapus `->skip()`.

**Acceptance Criteria:** AC-1 … AC-7 di checklist ATDD hijau; test PO/otorisasi/audit lama tetap hijau.

## UI checklist (ikuti pola resource Filament yang sudah ada; referensi aksi `submit` di halaman yang sama)

- Aksi header `Action::make()` dengan `->label()` Indonesia, `->icon(Heroicon::Outlined…)`, `->authorize()`, `->visible()`, `->modalSubmitActionLabel()` -- pola aksi `submit`.
- Warna tema: `success` (Setujui), `danger` (Tolak) -- selaras warna badge status.
- Form modal: `Textarea::make('note')`, `->required()` hanya untuk tolak.
- Error domain → `Notification::make()->danger()`; sukses → `->success()` -- pola `submit`.
- Entri infolist `TextEntry` dengan `->label()` + `->placeholder()` -- pola `creator.name`.

## Implementation Notes

- Diimplementasikan langsung tanpa subagent (aturan sesi: subagent hanya atas permintaan eksplisit); review step 4 dan verifikasi kesesuaian dengan pola resource Filament yang sudah ada dijalankan inline oleh agen yang sama, bukan reviewer independen.
- Temuan: `config/filament-shield.php` `define_via_gate = false`, jadi tidak ada `Gate::before`; admin lolos karena memegang semua permission (`RoleSeeder`). Ability `approve`/`reject` yang `return false` ikut menolak admin (AC-1/AC-2 gagal), maka Policy memakai `hasRole('admin')` mengikuti preseden `ProductPolicy::viewCost()` + cek status `Submitted`. Larangan pembuat tetap di domain (`PurchaseOrder::ensureCanBeDecidedBy()`). Koreksi dokumen agen (`brownfield.md`/`AGENTS.md`) di-defer.
- `reject('')` di domain melempar `AuthorizationException` (konsisten dengan `submit()`); `decision_note` kosong saat menyetujui disimpan `null`.
- Aksi `Setujui`/`Tolak` tersembunyi untuk pembuat PO dan PO non-diajukan; helper privat `decide()` di `ViewPurchaseOrder` berbagi pola notifikasi aksi `submit`.
- Migrasi `2026_11_08_055252_add_decision_columns_to_purchase_orders_table` sudah dijalankan di database dev. Tidak ada perubahan `RoleSeeder`.
- Belum diverifikasi di browser.

## Spec Change Log

## Review Triage Log

| Temuan | Verdict | Bukti / rute |
|---|---|---|
| Docblock kelas `PurchaseOrderPolicy` menyatakan admin melewati Policy lewat gate | low | Salah: `define_via_gate = false`. Patch: docblock dikoreksi. |
| `brownfield.md`/`AGENTS.md`/komentar `RoleSeeder` menyebut bypass `intercept_gate` | medium | Nyata; menyesatkan story berikut (ability kustom `false` menolak admin). Berkas konteks agen → defer. |
| Dua admin memutuskan bersamaan: Observer membandingkan status asli model yang dimuat, bukan baris DB terkunci | low | Nyata secara teori, jarang di satu gudang; fix butuh transaksi + `lockForUpdate` → ditolak. |
| Catatan kosong di `reject()` domain memakai `AuthorizationException`, bukan validasi | low | Disengaja, konsisten dengan `submit()`; ditolak. |
| Cabang sekunder tanpa test (catatan saat setujui, `decided_at`, `reject('')`, aksi tersembunyi untuk pembuat) | medium | Terdaftar di checklist ATDD untuk automate → defer. |

## Verification

**Commands:**
- `docker compose exec -T -u sail laravel.test php artisan test --compact tests/Feature/PurchaseOrderApprovalTest.php tests/Feature/PurchaseOrderDraftTest.php tests/Feature/PurchaseOrderDraftGuardsTest.php tests/Feature/AuthorizationMatrixTest.php tests/Feature/AuditTrailTest.php tests/Feature/CostMaskingTest.php` -- semua lulus, tanpa skip.
- `docker compose exec -T -u sail laravel.test vendor/bin/pint --dirty --format agent` -- bersih.
