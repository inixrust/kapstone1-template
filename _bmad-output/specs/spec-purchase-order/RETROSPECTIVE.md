---
date: 2026-11-08
verdict: accepted-with-open-items
criteria: declared
headless: true
---

# Retrospektif — Purchase Order ke Supplier

## Ringkasan epik

- **Epik:** `_bmad-output/specs/spec-purchase-order/` (stories mode; tanpa `sprint-status.yaml`).
- **Story (urutan `stories.yaml`):** 1 Buat draft PO dari produk kritis, 2 Persetujuan PO oleh admin, 3 Penerimaan barang PO — ketiganya `status: 'done'` di frontmatter artefak. `pending_stories` kosong.
- **Rentang diff:** baseline tercatat per story — S1 `eec9033`, S2 `94a8c86`, S3 `47e2533`; ujung S3 tidak tercatat, **diinferensi** `HEAD` = `4d88d83`. Karena baseline S1 adalah commit ATDD S1 sendiri, analisis agregat memakai rentang gabungan `eec9033^..4d88d83` agar ATDD story 1 ikut terbaca. `git_evidence.py`: 10 commit, 0 merge, 50 berkas, +5032/−5.
- **Atribusi commit:** S1 `eec9033` (ATDD), `77c6a7d` (feat), `59edd25` (automate); S2 `94a8c86`, `d871b12`, `eb23c02`; S3 `47e2533`, `cb99755`, `402476d`; tanpa story: `4d88d83` (trace gate).
- **Inventaris bukti:**
  - Ada: `SPEC.md`, `state-machines.md`, `brownfield.md`, 3 artefak story (Implementation Notes + Review Triage Log), checklist ATDD dan automation summary di `_bmad-output/test-artifacts/`, `traceability-matrix.md`, `gate-decision.json` (PASS: P0 100%, P1 90%, overall 91%), `_bmad-output/implementation-artifacts/deferred-work.md`.
  - Tidak ada: log sesi per story → analisis pelajaran proses hanya bersumber dari Implementation Notes/Review Triage Log di artefak story, bukan dari transkrip.
  - Tidak ada: retrospektif sebelumnya untuk epik ini.

## Temuan

Severitas: tinggi / sedang / rendah. Disposisi: *fix now* / *defer* / *accept*.

### Rekonsiliasi spec ↔ implementasi

**F1 — Fakta "admin melewati semua Policy" salah, tetapi tetap tertulis di kontrak dan kode (sedang, lintas story).**
`config/filament-shield.php:74-75` memakai `define_via_gate => false` dengan `intercept_gate => 'before'`; story 2 membuktikannya secara empiris (AC-1/AC-2 gagal saat ability `return false`, lihat Implementation Notes story 2). Klaim lama masih ada di: `SPEC.md:40` dan `:65`, `brownfield.md:21-22`, `AGENTS.md:44`, docblock `app/Models/PurchaseOrder.php:111` dan `:223`, `app/Filament/Resources/PurchaseOrders/Pages/EditPurchaseOrder.php:16`. Dampak nyata: story 3 harus menulis admin secara eksplisit di `PurchaseOrderPolicy::receive()` (`app/Policies/PurchaseOrderPolicy.php:84-91`) karena asumsi checklist ATDD tidak berlaku; dan `AGENTS.md:44` menyuruh agen mengabaikan test `adminUser()` atas dasar yang keliru. Di-defer dua kali (Review Triage story 2 dan 3; `deferred-work.md`). → **fix now** (koreksi kontrak, berkas konteks agen, dan docblock).

**F2 — Ability `approve`/`reject`/`receive` berbasis role, bukan permission Shield (rendah).**
`PurchaseOrderPolicy::canDecide()` memakai `hasRole('admin')` dan `receive()` memakai `hasAnyRole([...])` (`app/Policies/PurchaseOrderPolicy.php:71-95`), sedangkan `SPEC.md` Constraints menyebut "pola Filament Shield (`Action:PurchaseOrder`) plus ability kustom". Mengikuti preseden `ProductPolicy::viewCost()` dan `StockMovementPolicy::adjust()`; konsekuensinya hak ini tidak bisa diatur dari UI Shield. → **accept** (deviasi tercatat; konsisten dengan "hanya admin yang boleh menyetujui dan menolak").

**F3 — Format Rupiah PO berbeda dengan resource lama (rendah).**
PO memakai `money('IDR', locale: 'id')` (`PurchaseOrderInfolist.php`, `PurchaseOrdersTable.php:47`) → `Rp 12.500,00`; resource lama tampil `IDR 12,500.00` (locale app `en`). Sesuai SPEC ("prefix `Rp`") tetapi tidak konsisten lintas app. → **defer** (sudah di `deferred-work.md`).

### Pola dan divergensi antar story

**F4 — Penguncian konkurensi tidak seragam: `receive()` dikunci, `approve()`/`reject()` tidak (sedang, batas story 2↔3).**
`PurchaseOrder::receive()` memakai `DB::transaction` + `lockForUpdate` + cek ulang status (`app/Models/PurchaseOrder.php:168-201`) dan diuji dengan salinan basi (`tests/Feature/PurchaseOrderReceivingGuardsTest.php:30`). `decide()` (`:246-253`) langsung `save()`; `PurchaseOrderObserver::updating()` membandingkan `getRawOriginal('status')` milik instance yang dimuat (`app/Observers/PurchaseOrderObserver.php:47-53`), bukan baris DB. Instance basi berstatus Diajukan dapat menimpa PO yang di DB sudah Disetujui/Diterima menjadi Ditolak — transisi yang dilarang `state-machines.md`. Di UI Filament jendelanya hanya race antar-request (record dimuat ulang per request), jadi peluangnya kecil; tidak ada test stale/concurrent untuk keputusan (grep `tests/Feature` tanpa hasil). Story 2 menolak fix ini (Review Triage), lalu story 3 menerapkan pola yang justru menutupnya — tidak ada sesi yang melihat keduanya. → **defer** dengan rekomendasi: samakan `decide()` dengan pola `receive()`.

**F5 — Aturan "status + pembuat" diulang di tiga lapis (rendah).**
Status Diajukan dicek di `PurchaseOrderPolicy::canDecide()`, `PurchaseOrder::ensureCanBeDecidedBy()`, dan `ViewPurchaseOrder::canBeDecidedByCurrentUser()`; larangan pembuat di domain dan di `visible()` halaman. Defense in depth yang disengaja; risikonya hanya drift bila aturan berubah. Begitu juga `EditPurchaseOrder::authorizeAccess()` yang kini redundan dengan `PurchaseOrderPolicy::update()` (cek `isDraft()` lebih dulu). → **accept**.

**F6 — Penomoran `lockForUpdate` di luar transaksi pada jalur non-Filament (rendah).**
`PurchaseOrderObserver::nextNumber()` (`:63-77`); unique index pada `number` menjadi penjaga terakhir (error, bukan duplikat). Tidak ada test dua draft simultan. → **defer** (sudah di triage story 1).

### Ukuran / god-class

Volume perubahan tertinggi di kode aplikasi: `app/Models/PurchaseOrder.php` (+256/−2, kini 254 baris), `ViewPurchaseOrder.php` (+147), `PurchaseOrderPolicy.php` (+138, sebagian besar stub Shield). Setelah dibaca: kohesif, satu tanggung jawab per method; **bukan** god-class. Tidak ada temuan.

### Arsitektur

Dependensi baru: `PurchaseOrder` → `StockMovement` (create lewat model, `app/Models/PurchaseOrder.php:186-193`), `StockMovementObserver`/`StockMovementPolicy` → `StockMovement::isFromPurchaseOrder()`. Arah sesuai `AGENTS.md` (stok hanya lewat `StockMovement` model, Observer me-replay); `current_stock` tidak ditulis di diff. Tiga migrasi baru dengan `down()`, migrasi lama tidak disentuh. Tidak ada siklus atau pelanggaran lapisan yang terlihat (diturunkan manual dari berkas yang berubah; tidak ada tool graf dependensi PHP yang dijalankan).

### Ledger dan proses

**F7 — `deferred-work.md` basi setelah langkah automate (rendah, proses).**
Tiga entri "Tambah test cabang sekunder story 1/2/3" (`_bmad-output/implementation-artifacts/deferred-work.md`) sebagian besar sudah ditutup oleh commit `59edd25`, `eb23c02`, `402476d` (mis. `PurchaseOrderDraftGuardsTest.php:94,115,127`, `PurchaseOrderApprovalGuardsTest.php:14,56,79`, `PurchaseOrderReceivingGuardsTest.php:45,78,87`). Yang masih terbuka: dua draft simultan (F6) dan rollback migrasi `purchase_order_id` (tidak ada test). → **fix now** (rapikan ledger).

**F8 — Review di setiap story dijalankan inline oleh agen yang sama (proses).**
Implementation Notes story 1, 2, dan 3 menyatakan review step 4 "bukan reviewer independen". F4 adalah contoh temuan lintas story yang lolos karena itu. → pelajaran proses.

### Lensa diff (adversarial / edge case / verification gap)

Dijalankan **inline** atas kode aplikasi di rentang epik (model, observer, policy, halaman/skema Filament, migrasi, fixture E2E); `bmad-review` tidak dipanggil — cakupan dipersempit, test dan artefak `_bmad-output` hanya dibaca untuk cakupan. Temuan yang bertahan sudah masuk F1, F4, F6, F7. Dicek dan bersih: gating `viewCost` pada form (`PurchaseOrderForm.php` `unit_price`), infolist (`total`, `unit_price`, `subtotal`), dan tabel; `logOnly(['status'])` tidak membocorkan harga ke activity; fixture E2E membersihkan data lewat query builder dengan prefix `E2E PO` dan selalu `-u sail` (`tests/support/helpers/artisan.ts`).

## Verifikasi perilaku

- `docker compose exec -T -u sail laravel.test php artisan test --compact` → **287 passed (828 assertions)**. Ada peringatan non-fatal `file_put_contents(vendor/pestphp/pest/.temp/test-results): Permission denied` (cache hasil Pest; kemungkinan berkas milik root di `vendor/`), tidak memengaruhi hasil.
- `npx playwright test tests/e2e/purchase-order.spec.ts` terhadap app Sail di `http://localhost:8000` → **4 passed** (3 setup login + AC-10): manager membuat draft lewat UI (pilih supplier → item kritis terisi → jumlah 12) dan mengajukan, admin menyetujui, staff menerima; stok produk = 12; movement tertelusur ke nomor PO dan terlihat staff; staff tidak melihat "Harga per Item", "Subtotal", atau nilai `Rp`. Ini menutup catatan "belum diverifikasi di browser" di story 1 dan 2.
- Seluruh suite E2E (`npm run test:e2e`) tidak dijalankan dalam retro ini — hanya spec PO.

## Tindak lanjut retro sebelumnya

Tidak ada retrospektif sebelumnya untuk epik ini (`RETROSPECTIVE.md` belum ada) dan stories mode tidak memiliki `sprint-status.yaml` beserta `action_items`. Tidak ada yang perlu ditindaklanjuti; tidak ada transisi status yang diusulkan.

## Action items

Semua bersifat **usulan**; tidak ada yang diterapkan oleh retro ini.

| # | Aksi | Jenis | Pemilik | Sumber |
|---|---|---|---|---|
| A1 | Koreksi klaim bypass `intercept_gate` di `SPEC.md:40,65`, `brownfield.md:21-22`, `AGENTS.md:44`, docblock `PurchaseOrder.php:111,223`, `EditPurchaseOrder.php:16`: admin lolos karena memegang semua permission; ability yang tidak memeriksa permission menolak admin. | Rekonsiliasi spec + remediasi docblock | Fasilitator (Rustan) untuk SPEC/AGENTS; dev loop untuk docblock | F1 |
| A2 | Samakan `PurchaseOrder::approve()/reject()` dengan pola `receive()` (transaksi + `lockForUpdate` + cek ulang status) dan tambah test salinan basi untuk keputusan. | Remediasi (story kecil) | Dev loop | F4 |
| A3 | Perbarui `deferred-work.md`: tutup bagian cabang sekunder yang sudah diuji; sisakan test dua draft simultan dan rollback migrasi `purchase_order_id`. | Kebersihan ledger | Dev loop | F7, F6 |
| A4 | Putuskan format Rupiah seragam lintas aplikasi (locale `id` atau `money('IDR', locale: 'id')` di resource lama). | Keputusan produk, lalu remediasi | Fasilitator (Rustan) | F3 |
| A5 | Pelajaran proses: koreksi berkas konteks agen (`AGENTS.md`, `brownfield.md`) begitu sebuah fakta terbukti salah, jangan di-defer — story berikut membacanya sebagai kebenaran. | Proses | Fasilitator (Rustan) | F1 |
| A6 | Pelajaran proses: untuk epik multi-story, jalankan satu review lintas story (atau reviewer independen) sebelum story terakhir ditutup, agar pola yang dikeraskan di satu story diterapkan ke story lain. | Proses | Fasilitator (Rustan) | F4, F8 |

## Verdict penerimaan

**accepted-with-open-items** (kriteria **declared**: success signal dan kriteria CAP-1..3 di `SPEC.md`).

- CAP-1/2/3 terpenuhi dan teruji: suite Pest 287 lulus; trace gate `gate-decision.json` PASS (P0 100%, P1 90%, overall 91%).
- Success signal demo manager → admin → staff terbukti lewat E2E AC-10 (lulus di retro ini), termasuk stok bertambah tepat sebesar jumlah PO dan movement merujuk PO.
- Constraint utama terverifikasi di kode: state machine ditegakkan di domain, item terkunci di luar draft, `current_stock` tidak ditulis langsung, harga tersembunyi untuk staff, migrasi baru dengan `down()`, activity status dengan causer.
- Item terbuka (tidak memblokir): F1 (dokumen kontrak salah fakta), F4 (keputusan tanpa kunci konkurensi), F3, F6, F7.
- Semua story `done`; tidak ada keputusan manusia (headless) — verdict ini adalah verdict mesin.

## Pertanyaan terbuka

- Apakah ability approve/reject/receive memang harus berbasis role (F2), atau perlu permission Shield kustom agar bisa diatur dari UI?
- Apakah risiko race keputusan (F4) cukup penting untuk satu gudang, atau cukup diterima seperti keputusan story 2?
- Seluruh suite E2E (bukan hanya spec PO) belum dijalankan ulang di retro ini.

## Asumsi

- Epik dipilih dari argumen invocation (`_bmad-output/specs/spec-purchase-order/`); stories mode karena folder disebut eksplisit.
- Sesi non-interaktif dengan instruksi "ambil opsi paling sederhana, catat sebagai asumsi" → dijalankan sebagai headless: tanpa konfirmasi, tanpa diskusi tim (Phase 3 dilewati).
- `pending_stories` kosong (ketiga story `done`); completeness check berjalan dari frontmatter artefak, bukan `sprint-status.yaml`.
- Ujung rentang story 3 diinferensi `HEAD` (`4d88d83`), termasuk commit trace gate tanpa nomor story.
- Lensa review dijalankan inline (tanpa `bmad-review`), cakupan dipersempit ke kode aplikasi.
- Verdict `accepted-with-open-items` adalah verdict mesin tanpa keputusan manusia; action items A1–A6 adalah usulan.
- Tidak ada edit pada `SPEC.md`, `stories.yaml`, atau artefak story; tidak ada `sprint-status.yaml` yang dibuat.
