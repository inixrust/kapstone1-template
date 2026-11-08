---
workflowStatus: 'completed'
totalSteps: 5
stepsCompleted: ['step-01-detect-mode', 'step-02-load-context', 'step-03-risk-and-testability', 'step-04-coverage-plan', 'step-05-generate-output']
lastStep: 'step-05-generate-output'
nextStep: ''
lastSaved: '2026-11-08'
---

# Test Design: Epic purchase-order - Purchase Order ke Supplier

**Date:** 2026-11-08
**Author:** Rustan
**Status:** Draft

---

## Executive Summary

**Scope:** Test design tingkat epik untuk `SPEC-purchase-order` (CAP-1 buat & ajukan draft, CAP-2 persetujuan, CAP-3 penerimaan barang; story 1–3 di `_bmad-output/specs/spec-purchase-order/stories.yaml`). Fokus yang diminta: integritas stok, otorisasi (pembuat tidak boleh memutuskan PO sendiri), masking harga modal untuk staff, dan audit trail.

**Risk Summary:**

- Total risiko teridentifikasi: 13
- Risiko prioritas tinggi (≥6): 7 (satu di antaranya skor 9 = BLOCK: R-003)
- Kategori kritis: SEC (bypass admin Shield, matriks role, kebocoran harga) dan DATA (atomisitas penerimaan, keutuhan movement hasil PO, transisi status)

**Coverage Summary:**

- P0: 19 test (~20–35 jam)
- P1: 11 test (~8–15 jam)
- P2/P3: 7 test (~3–6 jam)
- Setup bersama (factory, fixture E2E, dataset): ~4–6 jam
- **Total effort**: ~35–60 jam (~1–1,5 minggu)

**Temuan utama:** Shield `intercept_gate = before` membuat `$admin->can(...)` selalu true. Karena itu tiga aturan inti (pembuat tidak boleh memutuskan PO sendiri, transisi status, dan kunci movement hasil PO) **harus** ditegakkan di jalur domain (model/aksi, mengikuti preseden `StockMovementObserver::creating()` yang melempar `AuthorizationException`) dan diuji dengan **admin** sebagai pelaku. Test yang hanya memakai Policy atau `userWithRole()` tidak membuktikan ketiga aturan ini.

---

## Not in Scope

| Item | Reasoning | Mitigation |
| --- | --- | --- |
| **Penerimaan sebagian** | Non-goal SPEC; penerimaan selalu penuh. | Test P0 memastikan jumlah movement = jumlah item PO (tidak ada jalur parsial). |
| **Kirim PO ke supplier (email/PDF)** | Non-goal SPEC. | Tidak ada. |
| **Notifikasi perubahan status** | Non-goal SPEC (konteks dry run: tidak ada notifikasi). | Tidak ada; test tidak menyertakan `Notification::fake()`. |
| **Mata uang selain Rupiah, multi-gudang, pembayaran** | Non-goal SPEC. | Test P3 hanya memeriksa format `Rp`. |
| **Ambang performa / beban** | SPEC tidak memberi ambang (lihat NFR Planning). | Status UNKNOWN; satu test P3 informasional (R-013). |
| **REST API / kontrak (Pact)** | Aplikasi tanpa REST API (Filament/Livewire). | Fragmen kontrak tidak dipakai; level "API" diganti test Pest Feature domain/Livewire. |

---

## Risk Assessment

Skor = Probabilitas (1–3) × Dampak (1–3). ≥6 wajib mitigasi sebelum release; 9 = BLOCK.

### High-Priority Risks (Score ≥6)

| Risk ID | Category | Description | Probability | Impact | Score | Mitigation | Owner | Timeline |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| R-003 | DATA | Movement hasil PO bisa diedit/dihapus: staff punya `Update:StockMovement`, manager `Delete:StockMovement`, admin bypass Policy. Edit/hapus memicu replay Observer → stok berubah dan jejak ke PO putus. | 3 | 3 | **9 (BLOCK)** | Penjagaan di model (`updating`/`deleting` movement ber-`purchase_order_id` melempar exception, berlaku juga untuk admin) + Policy + aksi edit/hapus disembunyikan di `StockMovementResource`. | Dev (story 3) | Sebelum story 3 selesai |
| R-001 | SEC | Admin pembuat PO menyetujui/menolak PO sendiri karena Shield `intercept_gate = before` meloloskan ability kustom. | 2 | 3 | 6 | Cek `created_by !== auth()->id()` di aksi domain approve/reject (bukan hanya Policy); test dengan `adminUser()` sebagai pembuat. | Dev (story 2) | Story 2 |
| R-002 | DATA | Penerimaan non-atomik atau ganda: sebagian movement tercipta lalu gagal, atau dua klik/request menerima PO yang sama dua kali → stok bertambah ganda. | 2 | 3 | 6 | `DB::transaction` + `lockForUpdate` pada PO, cek status `disetujui` di dalam transaksi; movement lewat `StockMovement::create()`. | Dev (story 3) | Story 3 |
| R-004 | SEC | Harga modal bocor ke staff lewat jalur baru: field harga item, subtotal, total di tabel/infolist/relation manager, atau properti activity log yang ditampilkan di audit. | 2 | 3 | 6 | Gate `can('viewCost', Product::class)` di setiap field/kolom/entri; activity PO hanya mencatat `status` (bukan harga); test Livewire + E2E DOM untuk staff. | Dev (story 1) | Story 1 |
| R-005 | DATA | Transisi status ilegal lolos di domain karena hanya disembunyikan di UI (mis. draft → disetujui, diterima → disetujui, edit item setelah diajukan, hapus PO non-draft). | 2 | 3 | 6 | State machine ditegakkan di model/aksi (tabel transisi `state-machines.md`); edit/hapus hanya untuk draft. | Dev (story 1–3) | Per story |
| R-006 | SEC | Matriks role PO salah: staff bisa membuat/mengajukan, manager bisa memutuskan, staff tidak bisa menerima; `RoleSeeder` lupa diperbarui. | 2 | 3 | 6 | Tambah `PurchaseOrder` ke `RoleSeeder` (`DOMAIN_RESOURCES`, `MANAGER_ACTIONS`, `STAFF_*`); perluas `AuthorizationMatrixTest`. | Dev (story 1) | Story 1 |
| R-012 | OPS | E2E memakai database dev: PO dan movement IN dari E2E menaikkan `current_stock` produk demo secara permanen, dan karena R-003 movement itu tidak bisa dihapus lewat UI. | 3 | 2 | 6 | Fixture E2E membuat supplier + produk kritis khusus test, lalu cleanup menghapus produk (FK `stock_movements.product_id` `cascadeOnDelete`) dan PO/supplier; tidak pernah memakai produk seed. | QA (story 3) | Story 3 |

### Medium-Priority Risks (Score 3-4)

| Risk ID | Category | Description | Probability | Impact | Score | Mitigation | Owner |
| --- | --- | --- | --- | --- | --- | --- | --- |
| R-007 | DATA | Nomor `PO-YYYYMM-####` duplikat atau salah urut (dua draft simultan; tidak reset di awal bulan). | 2 | 2 | 4 | Unique index di DB + `lockForUpdate` saat menghitung urutan; test `travelTo` lintas bulan. | Dev (story 1) |
| R-008 | BUS | Item awal salah: memuat produk tidak kritis, produk supplier lain, atau harga default ≠ `harga_modal`; membuat definisi "kritis" kedua. | 2 | 2 | 4 | Pakai `Product::withStockStatus('critical')` + filter `supplier_id`; test daftar awal. | Dev (story 1) |
| R-009 | DATA | Item tidak valid lewat bypass UI: produk supplier lain, produk ganda, jumlah < 1, harga < 0, supplier diganti setelah draft. | 2 | 2 | 4 | Validasi form + penjagaan model; unique (`purchase_order_id`, `product_id`). | Dev (story 1) |
| R-010 | OPS | Audit trail tidak lengkap: transisi tidak tercatat (mis. update lewat query builder / `saveQuietly`) atau causer salah. | 2 | 2 | 4 | `LogsActivity` dengan log name sendiri, `logOnly(['status'])`, `logOnlyDirty`; transisi lewat `save()` model. | Dev (story 1–3) |
| R-011 | TECH | Migrasi baru `purchase_order_id` di `stock_movements` mengganggu replay Observer, test lama, atau `down()` tidak bersih. | 1 | 3 | 3 | Kolom nullable + FK di migrasi baru (bukan migrasi lama); jalankan suite lama; test rollback. | Dev (story 3) |

### Low-Priority Risks (Score 1-2)

| Risk ID | Category | Description | Probability | Impact | Score | Action |
| --- | --- | --- | --- | --- | --- | --- |
| R-013 | PERF | Penerimaan PO dengan banyak item lambat (replay Observer per movement = O(item × movement produk)); tidak ada ambang di SPEC. | 1 | 2 | 2 | Document; test P3 informasional, ambang UNKNOWN. |

### Risk Category Legend

- **TECH**: Technical/Architecture (flaws, integration, scalability)
- **SEC**: Security (access controls, auth, data exposure)
- **PERF**: Performance (SLA violations, degradation, resource limits)
- **DATA**: Data Integrity (loss, corruption, inconsistency)
- **BUS**: Business Impact (UX harm, logic errors, revenue)
- **OPS**: Operations (deployment, config, monitoring)

**Residual risk:** Setelah mitigasi, R-001/R-003/R-005 bergantung pada semua jalur perubahan lewat model (bukan query builder). Pelanggaran konvensi itu di masa depan tidak tertangkap test epik ini; dicatat sebagai risiko residual rendah (konvensi sudah ada di `AGENTS.md`).

---

## NFR Planning

| NFR Category | Requirement / Threshold | Risk Link | Planned Validation | Evidence Needed |
| --- | --- | --- | --- | --- |
| Security | Biner 100%: pembuat tidak memutuskan PO sendiri; hanya admin yang memutuskan; staff tidak membuat/mengajukan; harga tersembunyi total untuk staff | R-001, R-004, R-006 | Pest Feature domain + Livewire (`userWithRole`, `adminUser` sebagai pembuat), E2E DOM staff | Laporan `php artisan test` (P0 SEC hijau), laporan Playwright |
| Reliability / Data integrity | Biner 100%: penerimaan atomik, tidak ganda; stok = jumlah movement; movement PO tidak bisa diubah/dihapus | R-002, R-003, R-005 | Pest Feature domain (rollback, penerimaan ulang, update/delete movement oleh admin) | Laporan Pest; query DB pasca-test |
| Auditability (compliance) | Setiap transisi status tercatat dengan causer | R-010 | Pest `AuditTrailTest` diperluas (dataset 4 transisi) | Laporan Pest |
| Maintainability | Suite lama tetap hijau; migrasi baru punya `down()` | R-011 | Full suite Pest + test rollback migrasi | Laporan full suite |
| Performance | **UNKNOWN** | R-013 | Test P3 informasional (PO banyak item) | Durasi test (informasi, bukan gate) |

**Unknown thresholds:** waktu respons penerimaan, jumlah item maksimal per PO, dan perilaku konkurensi (berapa request paralel) tidak didefinisikan di SPEC. Tidak ada nilai yang dikarang; keputusan akhir diserahkan ke `/bmad-testarch-nfr` (kemungkinan CONCERNS untuk performa).

---

## Entry Criteria

- [ ] SPEC, `state-machines.md`, dan asumsi SPEC disepakati (sudah tersedia; tanpa PRD/ADR terpisah)
- [ ] Sail berjalan; database MySQL `testing` tersedia (`phpunit.xml`)
- [ ] Factory `PurchaseOrderFactory` + `PurchaseOrderItemFactory` dengan state `draft`/`submitted`/`approved`/`rejected`/`received`
- [ ] Migrasi baru `purchase_orders`, `purchase_order_items`, dan `stock_movements.purchase_order_id` (nullable) tersedia
- [ ] `RoleSeeder` diperbarui dan `ShieldSeeder` dijalankan ulang untuk E2E
- [ ] Untuk E2E: app Sail sudah di-seed, akun demo aktif, fixture PO tersedia

## Exit Criteria

- [ ] Semua P0 lulus (100%)
- [ ] P1 ≥95% lulus (kegagalan wajib di-triage/waiver)
- [ ] R-003 (skor 9) termitigasi dan terbukti oleh test dengan admin sebagai pelaku
- [ ] Tidak ada bug terbuka berkategori SEC/DATA tingkat tinggi
- [ ] Full suite Pest lama tetap hijau; E2E alur utama manager → admin → staff hijau
- [ ] E2E meninggalkan database dev tanpa residu PO/movement/produk test

---

## Test Coverage Plan

> **Catatan:** P0/P1/P2/P3 menyatakan **prioritas** (dampak + risiko), bukan waktu eksekusi. Kapan test dijalankan diatur di Execution Strategy.

**Level test** (`test-levels-framework.md`, disesuaikan ke stack tanpa REST API):

- **Domain** = Pest Feature yang memanggil model/aksi langsung (setara level API/integration): aturan bisnis, state machine, transaksi, bypass admin.
- **Livewire** = Pest Feature dengan `Livewire::test()` pada page/resource Filament (setara component): visibilitas field/kolom/aksi per role.
- **E2E** = Playwright di host terhadap Sail: hanya alur utama lintas role dan render DOM untuk staff.

### P0 (Critical)

**Criteria**: Critical business, security, data-integrity, or compliance impact with no safe workaround. Risk score is supporting evidence and is not a required condition.

| ID | Requirement | Test Level | Risk Link | Test Count | Owner | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| PO-P0-01 | Admin pembuat **menyetujui** / **menolak** PO sendiri → exception, status tetap `diajukan`, tidak ada activity baru | Domain | R-001 | 2 | DEV | Wajib `adminUser()` sebagai `created_by`; Policy tidak cukup karena bypass. |
| PO-P0-02 | Manager dan staff tidak bisa menyetujui/menolak (aksi tersembunyi + panggilan langsung ditolak) | Livewire + Domain | R-001, R-006 | 2 | DEV | `userWithRole('manager')` & `userWithRole('staff')`; satu test per aksi dengan dataset role. |
| PO-P0-03 | Matriks otorisasi `PurchaseOrder` di `AuthorizationMatrixTest` (staff: ViewAny/View/terima; manager: + Create/Update/Delete/ajukan; tanpa setujui/tolak) | Domain | R-006 | 1 | DEV | Perluas dataset yang ada; jangan pakai `adminUser()`. |
| PO-P0-04 | Staff tidak bisa membuat atau mengajukan PO (halaman create forbidden + aksi ajukan ditolak) | Livewire | R-006 | 1 | DEV | |
| PO-P0-05 | Transisi ilegal ditolak di domain, juga untuk admin (dataset: draft→disetujui, draft→diterima, diajukan→diterima, ditolak→*, diterima→*, setujui/tolak PO non-diajukan) | Domain | R-005 | 1 | DEV | Dataset Pest; assert status tidak berubah. |
| PO-P0-06 | PO non-draft tidak bisa diubah item/harganya atau dihapus, termasuk oleh admin | Domain | R-005 | 1 | DEV | Dataset status diajukan/disetujui/ditolak/diterima. |
| PO-P0-07 | Penerimaan oleh staff: satu movement `In` per item, `quantity` = jumlah item, `purchase_order_id` = PO, `user_id` = penerima, catatan memuat nomor PO; `current_stock` tiap produk naik tepat sebesar jumlah; PO `diterima` | Domain | R-002 | 1 | DEV | Pakai `userWithRole('staff')`; assert `harga_modal` produk tidak berubah. |
| PO-P0-08 | Menerima PO yang bukan `disetujui` (termasuk yang sudah `diterima`) ditolak tanpa movement baru dan stok tetap | Domain | R-002, R-005 | 1 | DEV | Dataset status; hitung movement sebelum/sesudah. |
| PO-P0-09 | Penerimaan atomik: kegagalan pada item ke-n → rollback penuh (0 movement, stok tetap, status tetap `disetujui`) | Domain | R-002 | 1 | DEV | Picu gagal dengan event `creating` movement yang melempar pada produk tertentu. |
| PO-P0-10 | Movement yang merujuk PO tidak bisa di-**update** dan di-**hapus** di level model, termasuk oleh admin; stok tidak di-replay | Domain | R-003 | 2 | DEV | Mitigasi BLOCK; pakai `adminUser()` sebagai pelaku. |
| PO-P0-11 | Di `StockMovementResource`, aksi edit/hapus tidak tersedia untuk movement hasil PO bagi staff dan manager | Livewire | R-003 | 1 | DEV | Movement tanpa PO tetap mengikuti Policy lama (lihat PO-P1-09). |
| PO-P0-12 | Staff: field harga item, subtotal, dan total tersembunyi total di tabel PO dan halaman view/edit/relation manager item | Livewire | R-004 | 2 | DEV | Ikuti pola `CostMaskingTest` (`assertTableColumnHidden`, `assertFormFieldHidden`/infolist); kontrol positif manager terlihat dalam test yang sama. |
| PO-P0-13 | Audit PO yang dilihat staff tidak memuat nilai harga (properti activity hanya `status`) | Domain | R-004, R-010 | 1 | DEV | Assert `properties` activity tidak memuat kunci harga/total. |
| PO-P0-14 | Setiap transisi (ajukan, setujui, tolak, terima) mencatat activity dengan log name PO, `causer` = pelaku, status lama/baru | Domain | R-010 | 1 | DEV | Dataset 4 transisi; perluas/ikuti `AuditTrailTest`. Compliance → P0. |
| PO-P0-15 | E2E alur utama: manager membuat + mengajukan → admin menyetujui → staff menerima; stok produk test naik sebesar jumlah PO dan movement menunjuk PO | E2E | R-001, R-002, R-012 | 1 | QA | Success signal SPEC; fixture produk/supplier sendiri + cleanup. |

**Total P0**: 19 test, ~20–35 jam

### P1 (High)

**Criteria**: Core, frequent, or complex behavior with material user reach and a limited workaround. Risk score is supporting evidence and is not a required condition.

| ID | Requirement | Test Level | Risk Link | Test Count | Owner | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| PO-P1-01 | Item awal draft hanya produk kritis milik supplier terpilih (produk tidak kritis, produk supplier lain, produk tanpa supplier tidak muncul) | Livewire | R-008 | 1 | DEV | Pakai scope `withStockStatus('critical')`; data campuran. |
| PO-P1-02 | Harga default item = `harga_modal` produk saat draft dibuat; manager dapat mengubahnya | Livewire | R-008 | 1 | DEV | |
| PO-P1-03 | Produk tidak kritis milik supplier yang sama boleh ditambahkan; produk supplier lain ditolak di domain | Domain | R-009 | 1 | DEV | |
| PO-P1-04 | Nomor `PO-YYYYMM-####` berurutan dalam bulan dan reset ke `0001` di bulan baru | Domain | R-007 | 2 | DEV | `$this->travelTo()`; satu test urutan, satu test reset. |
| PO-P1-05 | Nomor PO unik di DB (insert duplikat gagal `UniqueConstraintViolationException`) | Domain | R-007 | 1 | DEV | Konkurensi nyata tidak diuji (lihat NFR UNKNOWN). |
| PO-P1-06 | PO tanpa item tidak bisa diajukan | Domain | R-005 | 1 | DEV | |
| PO-P1-07 | Penolakan wajib catatan; persetujuan boleh tanpa catatan | Livewire | R-005 | 1 | DEV | `assertHasActionErrors` untuk catatan kosong. |
| PO-P1-08 | Staff melihat daftar/detail PO dan aksi **Terima** tampil hanya pada PO `disetujui` | Livewire | R-006 | 1 | DEV | |
| PO-P1-09 | Regresi: movement tanpa PO tetap bisa diedit/dihapus sesuai Policy lama; replay Observer tetap benar dengan kolom `purchase_order_id` baru | Domain | R-003, R-011 | 1 | DEV | Menjaga penjagaan R-003 tidak terlalu luas. |
| PO-P1-10 | E2E staff: halaman daftar dan detail PO tidak merender teks/kolom harga (`Harga`, `Subtotal`, `Total`, `Rp`) | E2E | R-004 | 1 | QA | Pola `authorization.spec.ts`; satu-satunya bukti render nyata di DOM. |

**Total P1**: 11 test, ~8–15 jam

### P2 (Medium)

**Criteria**: Secondary behavior with narrower user reach and an acceptable workaround. Risk score is supporting evidence and is not a required condition.

| ID | Requirement | Test Level | Risk Link | Test Count | Owner | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| PO-P2-01 | Validasi item: jumlah ≥ 1, harga ≥ 0, produk unik per PO | Livewire | R-009 | 1 | DEV | Dataset nilai batas (0, -1, produk ganda). |
| PO-P2-02 | Supplier tidak bisa diganti setelah draft dibuat | Domain | R-009 | 1 | DEV | |
| PO-P2-03 | Manager dapat menghapus PO draft | Livewire | R-005 | 1 | DEV | Kontrol positif untuk PO-P0-06. |
| PO-P2-04 | Migrasi baru bisa di-rollback bersih (`down()`) | Domain | R-011 | 1 | DEV | `artisan migrate:rollback` pada DB testing, atau cek manual di review. |

**Total P2**: 4 test, ~2–4 jam

### P3 (Low)

**Criteria**: Rare, cosmetic, or experimental behavior with minimal impact and an easy workaround. Risk score is supporting evidence and is not a required condition.

| ID | Requirement | Test Level | Risk Link | Test Count | Owner | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| PO-P3-01 | Label UI Bahasa Indonesia (status draft/diajukan/disetujui/ditolak/diterima) dan resource di grup navigasi `Transaksi` | Livewire | - | 1 | DEV | Hanya jika label dihasilkan dari enum (bukan hardcode), agar test tidak sekadar mengulang setup. |
| PO-P3-02 | Harga untuk manager diformat `money('IDR')` dengan prefix `Rp` | Livewire | - | 1 | DEV | |
| PO-P3-03 | Penerimaan PO dengan banyak item (mis. 50) sukses; durasi dicatat sebagai informasi | Domain | R-013 | 1 | DEV | Bukan gate; ambang UNKNOWN. |

**Total P3**: 3 test, ~1–2 jam

### Pemetaan risiko → cakupan

| Risk | Skenario |
| --- | --- |
| R-001 | PO-P0-01, PO-P0-02, PO-P0-15 |
| R-002 | PO-P0-07, PO-P0-08, PO-P0-09, PO-P0-15 |
| R-003 | PO-P0-10, PO-P0-11, PO-P1-09 |
| R-004 | PO-P0-12, PO-P0-13, PO-P1-10 |
| R-005 | PO-P0-05, PO-P0-06, PO-P0-08, PO-P1-06, PO-P1-07, PO-P2-03 |
| R-006 | PO-P0-02, PO-P0-03, PO-P0-04, PO-P1-08 |
| R-007 | PO-P1-04, PO-P1-05 |
| R-008 | PO-P1-01, PO-P1-02 |
| R-009 | PO-P1-03, PO-P2-01, PO-P2-02 |
| R-010 | PO-P0-13, PO-P0-14 |
| R-011 | PO-P1-09, PO-P2-04 |
| R-012 | PO-P0-15 (fixture + cleanup) |
| R-013 | PO-P3-03 |

**Tanpa duplikasi lintas level:** aturan bisnis hanya dibuktikan di level Domain; Livewire hanya membuktikan visibilitas/aksi per role; E2E hanya membuktikan alur lintas role dan render DOM staff (dua test).

---

## Execution Strategy

**Philosophy:** Jalankan setiap skenario fungsional di pull request selama suite di bawah 15 menit. Tunda hanya pekerjaan yang mahal secara infrastruktur atau durasi. P0–P3 adalah prioritas, bukan waktu eksekusi.

- **Pull request:** seluruh test Pest (Domain + Livewire, P0–P3, termasuk suite lama) via `docker compose exec -T -u sail laravel.test php artisan test --compact`. Pest berjalan dalam hitungan detik–menit untuk ukuran suite ini.
- **Pull request / sebelum demo:** E2E Playwright (`npm run test:e2e`) — dua test PO ditambah suite E2E yang ada. Proyek ini sengaja memakai **satu worker** (database dev bersama, throttle login Filament 5/menit; `tests/README.md`), jadi paralelisasi Playwright tidak dipakai; durasi tetap jauh di bawah 15 menit.
- **Nightly/Weekly:** tidak ada yang wajib. Bila nanti ambang performa ditetapkan, PO-P3-03 dapat diperluas menjadi pengukuran terpisah.

Urutan saat triage manual: smoke (login + alur utama E2E) → P0 → P1 → P2/P3.

---

## Resource Estimates

### Test Development Effort

| Priority | Count | Effort Range | Notes |
| --- | --- | --- | --- |
| P0 | 19 | ~20–35 jam | Setup bypass admin, transaksi/rollback, dataset transisi, fixture E2E |
| P1 | 11 | ~8–15 jam | Livewire Filament standar, `travelTo` |
| P2 | 4 | ~2–4 jam | Validasi nilai batas |
| P3 | 3 | ~1–2 jam | Informasional |
| Setup bersama | - | ~4–6 jam | Factory + state, fixture E2E, perluasan `RoleSeeder`/matriks |
| **Total** | **37** | **~35–60 jam** | **~1–1,5 minggu** |

### Prerequisites

**Test Data:**

- `PurchaseOrderFactory` dengan state per status dan `created_by`; `PurchaseOrderItemFactory` (produk dari supplier PO, `quantity` ≥ 1, harga dari `harga_modal`)
- `ProductFactory` + state kritis (`current_stock <= reorder_point`) — stok awal dibentuk lewat movement, bukan menulis `current_stock`
- Fixture Playwright `tests/support/fixtures/purchase-order-fixture.ts`: membuat supplier + produk kritis khusus test, membersihkan PO, produk (cascade movement), dan supplier setelah test

**Tooling:**

- Pest 4 + plugin Livewire (`Livewire::test`) untuk Domain/Livewire
- Playwright (`@playwright/test`; `@seontechnologies/playwright-utils` tidak terpasang — jangan menambah dependensi tanpa persetujuan)

**Environment:**

- Laravel Sail; semua perintah lewat `docker compose exec -T -u sail laravel.test …` (jangan pernah tanpa `-u sail`)
- DB MySQL `testing` untuk Pest; database dev ter-seed untuk E2E

---

## Quality Gate Criteria

### Pass/Fail Thresholds

- **P0 pass rate**: 100% (tanpa pengecualian)
- **P1 pass rate**: ≥95% (kegagalan butuh waiver)
- **P2/P3 pass rate**: ≥90% (informasional)
- **High-risk mitigations**: 100% selesai; R-003 tidak dapat di-waive

### Coverage Targets

- **Critical paths** (alur draft → diajukan → disetujui → diterima): ≥80%
- **Security scenarios** (R-001, R-004, R-006): 100%
- **Business logic** (state machine, penomoran, item awal): ≥70%
- **Edge cases**: ≥50%

### Non-Negotiable Requirements

- [ ] Semua P0 lulus
- [ ] Tidak ada risiko ≥6 yang belum termitigasi
- [ ] Skenario SEC lulus 100%
- [ ] Aturan bypass-sensitive (R-001, R-003, R-005) dibuktikan dengan admin sebagai pelaku
- [ ] Performa: ambang UNKNOWN → dicatat sebagai CONCERNS di `nfr-assess`, bukan gate epik ini

---

## Mitigation Plans

### R-003: Movement hasil PO bisa diedit/dihapus (Score: 9, BLOCK)

**Mitigation Strategy:** Tambah penjagaan di model `StockMovement` (event `updating`/`deleting`, atau di `StockMovementObserver`) yang melempar `AuthorizationException` bila `purchase_order_id` terisi — berlaku untuk semua user termasuk admin. Policy `update`/`delete` mengembalikan false untuk movement ber-PO, dan aksi edit/hapus di tabel/halaman disembunyikan.
**Owner:** Dev story 3
**Timeline:** Sebelum story 3 dinyatakan selesai
**Status:** Planned
**Verification:** PO-P0-10 (admin), PO-P0-11 (staff/manager), PO-P1-09 (movement tanpa PO tidak terdampak).

### R-001: Admin pembuat memutuskan PO sendiri (Score: 6)

**Mitigation Strategy:** Aksi domain `approve`/`reject` memeriksa `created_by !== $actor->id` sebelum Policy, melempar exception; aksi Filament juga disembunyikan untuk pembuat.
**Owner:** Dev story 2
**Timeline:** Story 2
**Status:** Planned
**Verification:** PO-P0-01 dengan `adminUser()` sebagai pembuat dan pelaku; PO-P0-15 memakai admin yang berbeda dari manager pembuat.

### R-002: Penerimaan non-atomik/ganda (Score: 6)

**Mitigation Strategy:** Aksi terima dalam `DB::transaction`, PO di-`lockForUpdate`, status dicek ulang di dalam transaksi, movement via `StockMovement::create()` per item, status `diterima` di akhir transaksi.
**Owner:** Dev story 3
**Timeline:** Story 3
**Status:** Planned
**Verification:** PO-P0-07, PO-P0-08, PO-P0-09.

### R-004: Harga modal bocor ke staff (Score: 6)

**Mitigation Strategy:** Setiap field/kolom/entri harga, subtotal, total memakai `->visible(fn () => Auth::user()?->can('viewCost', Product::class) ?? false)`; activity PO `logOnly(['status'])`.
**Owner:** Dev story 1
**Timeline:** Story 1
**Status:** Planned
**Verification:** PO-P0-12, PO-P0-13, PO-P1-10.

### R-005: Transisi status ilegal lolos di domain (Score: 6)

**Mitigation Strategy:** Satu titik transisi di model (mis. method per transisi yang memvalidasi status asal sesuai `state-machines.md`); penjagaan `updating`/`deleting` untuk PO non-draft.
**Owner:** Dev story 1–3
**Timeline:** Per story
**Status:** Planned
**Verification:** PO-P0-05, PO-P0-06, PO-P0-08.

### R-006: Matriks role PO salah (Score: 6)

**Mitigation Strategy:** Tambah `PurchaseOrder` di `RoleSeeder`; ability kustom (ajukan, setujui, tolak, terima) di Policy; perluas `AuthorizationMatrixTest`.
**Owner:** Dev story 1
**Timeline:** Story 1
**Status:** Planned
**Verification:** PO-P0-02, PO-P0-03, PO-P0-04, PO-P1-08.

### R-012: Residu data E2E di database dev (Score: 6)

**Mitigation Strategy:** Fixture membuat data sendiri (supplier + produk kritis dengan nama unik), cleanup di `afterEach`/teardown fixture menghapus produk (cascade movement) dan PO; tidak pernah menerima PO atas produk seed.
**Owner:** QA story 3
**Timeline:** Story 3
**Status:** Planned
**Verification:** Setelah `npm run test:e2e`, hitungan PO/movement/produk di database dev kembali ke nilai sebelum run.

---

## Assumptions and Dependencies

### Assumptions

1. Semua asumsi di bagian "Assumptions" `SPEC.md` berlaku (penerimaan penuh & atomik, catatan wajib saat menolak, movement ber-PO terkunci, staff melihat PO tanpa harga).
2. Penolakan pembuat dan transisi ilegal melempar `AuthorizationException` (preseden `StockMovementObserver::creating()`); bila dev memilih exception domain lain, test menyesuaikan jenis exception — asumsi paling sederhana yang konsisten dengan kode.
3. PO menyimpan `created_by` (pembuat) sebagai dasar aturan R-001.
4. Rujukan movement ke PO lewat kolom nullable `stock_movements.purchase_order_id` (migrasi baru).
5. Penghapusan produk test di E2E menghapus movement-nya (FK `cascadeOnDelete` yang ada) dan Observer tidak perlu me-replay produk yang sudah terhapus.
6. Konkurensi nyata (dua request paralel) tidak diuji otomatis; penjagaan dibuktikan lewat constraint DB + review kode `lockForUpdate`.

### Dependencies

1. Story 1 (model, migrasi, factory, `RoleSeeder`) — dibutuhkan sebelum test story 2 dan 3 bisa hijau.
2. Story 2 (aksi setujui/tolak) — dibutuhkan sebelum PO-P0-07..10 dan E2E.
3. Fixture E2E PO — dibutuhkan sebelum PO-P0-15 dan PO-P1-10.

### Risks to Plan

- **Risk**: Throttle login Filament (5/menit) memperlambat E2E lintas tiga role.
  - **Impact**: E2E alur utama gagal/flaky.
  - **Contingency**: Pakai sesi tersimpan per role dari `auth.setup.ts`, bukan login ulang di test.
- **Risk**: Cleanup gagal di tengah run meninggalkan produk test dengan stok.
  - **Impact**: Data demo tercemar.
  - **Contingency**: Nama data test berprefiks unik; fixture membersihkan sisa run sebelumnya saat setup.

---

## Panduan untuk `/bmad-testarch-atdd` (red phase)

Jalankan per story, P0 lebih dulu. Semua test baru di `tests/Feature/` (bukan mirror `app/`), memakai `it('...')` bahasa Inggris tanpa komentar, mengikuti berkas sibling.

| Story | Berkas test (usulan) | Skenario red-phase |
| --- | --- | --- |
| 1 | `tests/Feature/PurchaseOrderDraftTest.php` | PO-P0-04, PO-P0-05 (bagian draft), PO-P0-06, PO-P1-01..06, PO-P2-01..03 |
| 1 | perluas `AuthorizationMatrixTest.php`, `CostMaskingTest.php`, `AuditTrailTest.php` | PO-P0-03, PO-P0-12, PO-P0-13, PO-P0-14 (ajukan) |
| 2 | `tests/Feature/PurchaseOrderApprovalTest.php` | PO-P0-01, PO-P0-02, PO-P0-05 (keputusan), PO-P0-14 (setujui/tolak), PO-P1-07 |
| 3 | `tests/Feature/PurchaseOrderReceivingTest.php` | PO-P0-07..11, PO-P0-14 (terima), PO-P1-08, PO-P1-09, PO-P2-04, PO-P3-03 |
| 3 | `tests/e2e/purchase-order.spec.ts` + `tests/support/fixtures/purchase-order-fixture.ts` | PO-P0-15, PO-P1-10 |

Aturan red-phase:

- Untuk aturan bypass-sensitive (PO-P0-01, PO-P0-05, PO-P0-06, PO-P0-10) pelaku **wajib** `adminUser()`; test yang memakai `userWithRole()` di sini lulus palsu karena Policy saja sudah menolak.
- Untuk bukti akses per role (PO-P0-02..04, PO-P1-08) pakai `userWithRole('staff')`/`userWithRole('manager')`, bukan `adminUser()`.
- Stok awal produk dibuat lewat `StockMovement::create()`; jangan menulis `current_stock`.
- Setiap test harus gagal karena fitur belum ada (class/route/aksi tidak ditemukan), bukan karena setup salah; jalankan dengan `docker compose exec -T -u sail laravel.test php artisan test --compact --filter=PurchaseOrder`.

## Panduan untuk `/bmad-testarch-automate`

Setelah implementasi ada dan test ATDD hijau:

- Lengkapi P1–P3 yang belum dibuat di fase ATDD, terutama PO-P1-04/05 (penomoran) dan PO-P1-09 (regresi movement tanpa PO).
- Ubah skenario berulang menjadi dataset Pest (`->with([...])`) — transisi ilegal, status penerimaan, role × aksi — bukan test yang disalin.
- Jangan menduplikasi aturan bisnis di E2E; E2E tetap dua test (alur utama + render DOM staff).
- Verifikasi tidak ada residu E2E di database dev setelah run (lihat R-012).
- Setelah semua hijau, minta user menjalankan full suite: `docker compose exec -T -u sail laravel.test php artisan test --compact` dan `npm run test:e2e`.

---

## Follow-on Workflows (Manual)

- Run `/bmad-testarch-atdd` to generate failing P0 tests (separate workflow; not auto-run).
- Run `/bmad-testarch-automate` for broader coverage once implementation exists.
- Run `/bmad-testarch-nfr` setelah implementasi untuk keputusan bukti NFR (performa diperkirakan CONCERNS karena ambang UNKNOWN).

---

## Approval

**Test Design Approved By:**

- [ ] Product Manager: ______ Date: ______
- [ ] Tech Lead: ______ Date: ______
- [ ] QA Lead: ______ Date: ______

**Comments:**

---

## Interworking & Regression

| Service/Component | Impact | Regression Scope |
| --- | --- | --- |
| **StockMovement + Observer** | Kolom `purchase_order_id` baru; penjagaan update/delete movement ber-PO | `StockMovementTest`, `HardeningTest`, PO-P1-09 |
| **Otorisasi (RoleSeeder/Shield)** | Resource baru + ability kustom | `AuthorizationMatrixTest`, `RolePermissionTest`, `tests/e2e/authorization.spec.ts` |
| **Masking harga modal** | Field/kolom harga baru di PO | `CostMaskingTest`, `ProductResourceTest` |
| **Audit trail** | Log name baru untuk PO | `AuditTrailTest` |
| **Dashboard / analytics** | Stok bertambah lewat movement IN (tidak ada perubahan service) | `DashboardBasicTest`, `AdvancedDashboardTest`, `CriticalDeadStockTest`, `MovementTrendTest` |

---

## Appendix

### Knowledge Base References

- `risk-governance.md` - Risk classification framework
- `probability-impact.md` - Risk scoring methodology
- `test-levels-framework.md` - Test level selection
- `test-priorities-matrix.md` - P0-P3 prioritization
- `nfr-criteria.md` - NFR planning
- `playwright-cli.md` - Eksplorasi browser (dilewati: halaman PO belum ada)

### Related Documents

- PRD: tidak ada (SPEC menggantikan)
- Epic/Spec: `_bmad-output/specs/spec-purchase-order/SPEC.md`, `stories.yaml`
- Architecture: `_bmad-output/specs/spec-purchase-order/brownfield.md`, `state-machines.md`
- Project rules: `AGENTS.md`, `tests/README.md`

---

**Generated by**: BMad TEA Agent - Test Architect Module
**Workflow**: `bmad-testarch-test-design`
**Version**: 4.0 (BMad v6)
