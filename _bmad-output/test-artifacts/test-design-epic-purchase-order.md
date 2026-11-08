---
workflowStatus: 'completed'
totalSteps: 5
stepsCompleted: ['step-01-detect-mode', 'step-02-load-context', 'step-03-risk-and-testability', 'step-04-coverage-plan', 'step-05-generate-output']
lastStep: 'step-05-generate-output'
nextStep: ''
lastSaved: '2026-11-08'
inputDocuments:
  - _bmad-output/specs/spec-purchase-order/SPEC.md
  - _bmad-output/specs/spec-purchase-order/stories.yaml
  - _bmad-output/specs/spec-purchase-order/state-machines.md
  - _bmad-output/specs/spec-purchase-order/brownfield.md
  - AGENTS.md
  - tests/README.md
---

# Test Design: Epic purchase-order — Purchase Order ke Supplier

**Tanggal:** 2026-11-08
**Penulis:** Rustan (disusun oleh TEA — Master Test Architect)
**Status:** Draft

---

## Ringkasan Eksekutif

**Cakupan:** test design level epik (full) untuk `_bmad-output/specs/spec-purchase-order/` — story 1 (draft & ajukan, CAP-1), story 2 (persetujuan admin, CAP-2), story 3 (penerimaan barang, CAP-3).

**Ringkasan risiko:**

- Total risiko: 13
- Risiko prioritas tinggi (≥6): 7, termasuk **1 kritis (skor 9, R-003)** yang otomatis membuat gate FAIL bila belum termitigasi
- Kategori dominan: DATA (integritas stok & jejak PO), SEC (pemisahan tugas, masking harga modal, matriks role)

**Ringkasan cakupan:**

- P0: 19 skenario (~25–40 jam)
- P1: 11 skenario (~10–18 jam)
- P2/P3: 7 skenario (~3–7 jam)
- **Total:** 37 skenario, ~38–65 jam (~1–2 minggu, termasuk factory dan fixture)

Empat fokus yang diminta dipetakan ke risiko berikut: **integritas stok** → R-002, R-003, R-005; **otorisasi / pembuat tidak boleh memutuskan PO sendiri** → R-001, R-006; **masking harga modal untuk staff** → R-004; **audit trail** → R-009.

---

## Tidak Termasuk Cakupan

| Item | Alasan | Mitigasi |
| --- | --- | --- |
| Penerimaan sebagian | Non-goal SPEC | Test PO.3-INT-001 menegaskan penerimaan selalu penuh |
| Kirim PO ke supplier (email/PDF), notifikasi | Non-goal SPEC | Tidak ada test; bila ditambahkan, test design diperbarui |
| Mata uang selain Rupiah, multi-gudang, pembayaran | Non-goal SPEC | — |
| Test kontrak/API | Aplikasi tanpa REST API (Livewire saja) | Lapis Pest Livewire menggantikan lapis API |
| Load test (k6) | Tidak ada ambang performa di SPEC | Sanity jumlah query di P3 (PO.3-INT-009) |

---

## Penilaian Risiko

Skala: Probabilitas 1 (jarang) – 3 (mungkin besar); Dampak 1 (minor) – 3 (kritis). Skor = P × I. 1–3 DOCUMENT, 4–5 MONITOR, 6–8 MITIGATE, 9 BLOCK.

### Risiko Prioritas Tinggi (Skor ≥6)

| Risk ID | Kategori | Deskripsi (sumber) | P | I | Skor | Mitigasi | Owner | Timeline |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| R-003 | DATA | Movement IN hasil PO bisa diedit/dihapus sehingga stok di-replay ulang dan jejak ke PO putus. Sumber: brownfield "`StockMovementPolicy` memberi staff `Update:StockMovement` dan manager `Delete:StockMovement`" + admin bypass. Izin itu sudah ada hari ini, jadi P = 3. | 3 | 3 | **9** | Kunci di domain (model event `updating`/`deleting` menolak bila `purchase_order_id` terisi, kebal bypass admin) + Policy `update`/`delete` + sembunyikan aksi tabel/edit. Bukti: PO.3-INT-004, PO.3-INT-005, PO.3-INT-006 | Dev story 3 | Sebelum story 3 selesai |
| R-001 | SEC | Admin pembuat menyetujui/menolak PO sendiri karena Shield `intercept_gate = before` membuat `$admin->can()` selalu true. Sumber: SPEC Constraints + brownfield "Jebakan". | 2 | 3 | 6 | Penegakan di jalur domain (method transisi membandingkan `created_by` dengan pelaku), bukan hanya Policy; test memakai admin sebagai pembuat. Bukti: PO.2-INT-001..003, PO.2-INT-005 | Dev story 2 | Sebelum story 2 selesai |
| R-002 | DATA | Penerimaan tidak atomik (sebagian movement tercipta), penerimaan ganda, atau `current_stock` ditulis langsung. Sumber: SPEC Assumptions "penerimaan selalu penuh dan atomik" + CAP-3 "menerima PO yang sudah diterima ditolak tanpa movement baru". | 2 | 3 | 6 | `DB::transaction` + `lockForUpdate` pada PO, cek status di dalam transaksi, movement via `StockMovement::create()`. Bukti: PO.3-INT-001..003, PO.3-E2E-001 | Dev story 3 | Sebelum story 3 selesai |
| R-004 | SEC | Harga per item, subtotal, atau total PO terlihat staff (kolom, entri detail, repeater item, properti activity log). Sumber: SPEC Constraints "tersembunyi total untuk staff … bukan `****`" + AGENTS.md. | 2 | 3 | 6 | Gate tiap field/kolom/entri dengan `can('viewCost', Product::class)`; activity log PO hanya mencatat status/catatan. Bukti: PO.1-INT-004/005, PO.1-INT-013, PO.3-E2E-002 | Dev story 1 | Sebelum story 1 selesai |
| R-005 | BUS | Transisi status ilegal lolos karena hanya disembunyikan di UI (mis. terima PO draft, setujui PO draft, edit/hapus PO diajukan/final). Sumber: state-machines.md "ditolak di level domain, bukan hanya disembunyikan di UI". | 2 | 3 | 6 | Satu tabel transisi di domain; semua aksi lewat method transisi. Bukti: PO.1-INT-006/007, PO.2-INT-006, PO.3-INT-002 | Dev story 1–3 | Per story |
| R-006 | SEC | Matriks role PO salah: staff bisa membuat/mengajukan, manager bisa memutuskan, atau `PurchaseOrder` tidak ditambahkan ke `RoleSeeder`. Sumber: SPEC Constraints "Resource baru masuk `RoleSeeder` dan `AuthorizationMatrixTest`" + "Hanya admin yang boleh menyetujui dan menolak". | 2 | 3 | 6 | Tambah baris dataset `AuthorizationMatrixTest`; test domain untuk staff/manager. Bukti: PO.1-INT-001..003, PO.2-INT-004, PO.3-INT-007 | Dev story 1–2 | Per story |
| R-012 | OPS | E2E berjalan di database dev; movement hasil PO sengaja tidak bisa dihapus (R-003), sehingga fixture tidak bisa membersihkan stok yang ditambah. Sumber: stories.yaml story 3 "data PO dan movement yang dibuat harus dibersihkan fixture" + brownfield. | 3 | 2 | 6 | Fixture membuat supplier + produk sekali pakai (`E2E-<uuid>`) via UI; tidak pernah menyentuh produk demo; residu terbatas pada record sekali pakai (lihat Asumsi A-6). Bukti: PO.3-E2E-001 | QA story 3 | Saat E2E dibuat |

### Risiko Prioritas Menengah (Skor 3–4)

| Risk ID | Kategori | Deskripsi (sumber) | P | I | Skor | Mitigasi | Owner |
| --- | --- | --- | --- | --- | --- | --- | --- |
| R-007 | DATA | Nomor `PO-YYYYMM-####` duplikat atau urutan tidak reset per bulan. Sumber: brownfield "Nomor urut per bulan butuh penjagaan konkurensi". | 2 | 2 | 4 | Unique index + kunci saat menghitung urutan; test format/reset/unik | Dev story 1 |
| R-008 | BUS | Item awal salah: memuat produk tidak kritis, produk supplier lain, produk tanpa supplier, atau harga ≠ `harga_modal`. Sumber: CAP-1 success + brownfield (`withStockStatus('critical')`, jangan definisi kritis kedua). | 2 | 2 | 4 | Pakai scope yang ada; test PO.1-INT-008, PO.1-INT-015 | Dev story 1 |
| R-009 | DATA | Perubahan status PO tidak tercatat di activitylog atau causer salah (mis. transisi via `saveQuietly`/query builder). Sumber: SPEC Constraints "Setiap perubahan status PO tercatat … beserta pelakunya". Satu-satunya bukti bahwa pemisahan tugas dipatuhi. | 2 | 2 | 4 | `LogsActivity` dengan log name sendiri, `logOnlyDirty`; test PO.2-INT-007 | Dev story 1–3 |
| R-010 | DATA | Validasi item lolos: jumlah < 1, harga < 0, produk ganda, produk supplier lain, supplier diganti setelah draft. Sumber: SPEC Assumptions. | 2 | 2 | 4 | Validasi form + domain; test PO.1-INT-009, PO.1-INT-012, PO.1-INT-014 | Dev story 1 |

### Risiko Prioritas Rendah (Skor 1–2)

| Risk ID | Kategori | Deskripsi (sumber) | P | I | Skor | Aksi |
| --- | --- | --- | --- | --- | --- | --- |
| R-011 | BUS | Penolakan tanpa catatan diterima. Sumber: SPEC Assumptions "Catatan wajib saat menolak". | 1 | 2 | 2 | Document; test PO.2-INT-008 |
| R-013 | PERF | Observer me-replay semua movement per produk untuk tiap item, sehingga penerimaan PO besar lambat. Sumber: brownfield "replay semua movement per produk". | 1 | 2 | 2 | Document; sanity PO.3-INT-009 |

### Legenda Kategori

- **TECH**: arsitektur/integrasi · **SEC**: akses, data sensitif · **PERF**: performa · **DATA**: integritas data · **BUS**: logika bisnis · **OPS**: operasional/lingkungan

---

## Perencanaan NFR

| Kategori NFR | Requirement / Ambang | Risk Link | Validasi Terencana | Bukti yang Dibutuhkan |
| --- | --- | --- | --- | --- |
| Security — pemisahan tugas | Pembuat PO (termasuk admin) tidak bisa memutuskan; hanya admin yang memutuskan. Ambang: biner, 100% skenario lulus | R-001, R-006 | Pest INT domain + Livewire action, dengan `adminUser()` sebagai **pembuat** dan `userWithRole()` untuk role lain | Laporan `php artisan test` (JUnit) untuk `PurchaseOrderApprovalTest` + `AuthorizationMatrixTest` |
| Security — kerahasiaan harga modal | Tersembunyi total untuk staff (bukan `****`). Ambang: biner | R-004 | Pest Livewire (`assertTableColumnHidden`, `assertDontSee` nilai unik) + E2E render staff | Laporan Pest + Playwright HTML/JUnit |
| Reliabilitas — atomisitas penerimaan | Semua movement + status dalam satu transaksi; penerimaan ganda ditolak. Ambang: biner | R-002 | Pest INT dengan kegagalan yang diinjeksi pada item ke-2 | Laporan Pest `PurchaseOrderReceiptTest` |
| Integritas / auditabilitas | Setiap transisi tercatat dengan causer; movement ber-PO tidak bisa diubah | R-003, R-009 | Pest INT terhadap tabel `activity_log` dan model `StockMovement` | Laporan Pest `AuditTrailTest` + `PurchaseOrderReceiptTest` |
| Maintainability | Suite Pest lama tetap hijau; Pint bersih | — | `php artisan test --compact`, `vendor/bin/pint --dirty` | Output CI/lokal |

**Ambang yang belum diketahui (UNKNOWN):** waktu respons penerimaan PO dan jumlah item maksimum per PO; tingkat konkurensi pembuatan draft. Tidak ditebak — dicatat sebagai pertanyaan klarifikasi, dan sementara dicakup oleh sanity P3 (PO.3-INT-009) dan unique index (PO.1-INT-011).

---

## Kriteria Masuk

- [ ] SPEC, state-machines, dan brownfield disepakati (sudah ada di `_bmad-output/specs/spec-purchase-order/`)
- [ ] Sail berjalan; database `testing` tersedia; dev DB sudah di-seed untuk E2E
- [ ] `PurchaseOrderFactory` + `PurchaseOrderItemFactory` dengan state status tersedia (dibuat saat ATDD)
- [ ] `ShieldSeeder` meregenerasi permission `Action:PurchaseOrder`, `RoleSeeder` memuat `PurchaseOrder`
- [ ] Migrasi baru (PO, item, rujukan `stock_movements` → PO) berjalan di database `testing`

## Kriteria Keluar

- [ ] Semua P0 lulus (100%)
- [ ] P1 ≥95% lulus; kegagalan sudah ditriase dan punya waiver
- [ ] R-003 termitigasi dan dibuktikan oleh PO.3-INT-004 (gate FAIL otomatis bila belum)
- [ ] Seluruh suite Pest lama hijau dan E2E alur manager → admin → staff hijau (success signal SPEC)
- [ ] Tidak ada bug high-severity terbuka di area SEC/DATA

---

## Rencana Cakupan Test

> **Catatan:** P0/P1/P2/P3 menyatakan **prioritas** (dampak bisnis × jangkauan × ketiadaan workaround), **bukan** waktu eksekusi. Waktu eksekusi diatur di bagian Strategi Eksekusi.

**Level test di proyek ini:**

- **INT** = Pest Feature (`tests/Feature/`, `RefreshDatabase`, MySQL `testing`): domain/model, Policy, dan komponen Filament via `Pest\Livewire\livewire()`. Di aplikasi tanpa REST API, lapis ini setara lapis API/komponen sekaligus.
- **E2E** = Playwright (`tests/e2e/`, database dev): hanya alur yang butuh browser sungguhan.
- **MAN** = manual/eksploratif.

Format ID: `PO.{story}-{LEVEL}-{SEQ}`.

### P0 (Kritis)

**Kriteria:** dampak bisnis, keamanan, integritas data, atau kepatuhan yang kritis tanpa workaround aman.

| ID | Requirement / Skenario | Level | Risk Link | Jumlah | Owner | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| PO.1-INT-001 | Staff tidak bisa membuat PO: `can('create', PurchaseOrder::class)` false dan halaman Create → 403 | INT | R-006 | 1 | DEV | `userWithRole('staff')` |
| PO.1-INT-002 | Baris matriks `PurchaseOrder` di `AuthorizationMatrixTest`: staff `ViewAny/View` true, `Create/Update/Delete` false; manager `Create/Update/Delete` true, `ForceDelete` false | INT | R-006 | 1 (dataset ±10 baris) | DEV | Tambah ke dataset yang ada, bukan file baru |
| PO.1-INT-003 | Staff tidak bisa mengajukan PO lewat method domain (melewati UI) | INT | R-006 | 1 | DEV | Panggil method domain langsung |
| PO.1-INT-004 | Staff di List & View PO: kolom/entri harga, subtotal, total tersembunyi; nilai harga unik tidak muncul di HTML | INT | R-004 | 2 | DEV | `assertTableColumnHidden` + `assertDontSee('123.457')` dengan harga unik |
| PO.1-INT-005 | Manager dan admin melihat field/kolom harga yang sama | INT | R-004 | 1 | DEV | Pasangan positif agar PO.1-INT-004 tidak lolos karena kolom tidak pernah ada |
| PO.1-INT-006 | Draft dengan ≥1 item → diajukan; draft tanpa item ditolak; status tetap draft | INT | R-005 | 2 | DEV | — |
| PO.1-INT-007 | PO diajukan/disetujui/ditolak/diterima tidak bisa diubah item/harganya atau dihapus, termasuk oleh admin | INT | R-005 | 1 (dataset 4 status) | DEV | Uji di domain dengan `adminUser()` — Policy saja tidak cukup |
| PO.2-INT-001 | Admin pembuat **menyetujui** PO sendiri → ditolak, status tetap diajukan | INT | R-001 | 1 | DEV | Pembuat = `adminUser()`; inti larangan memutuskan sendiri |
| PO.2-INT-002 | Admin pembuat **menolak** PO sendiri (dengan catatan) → ditolak | INT | R-001 | 1 | DEV | — |
| PO.2-INT-003 | Aksi Filament setujui/tolak tersembunyi untuk admin pembuat; pemanggilan paksa aksi tidak mengubah status | INT | R-001 | 1 | DEV | Livewire `assertActionHidden` + `callAction` lalu cek DB |
| PO.2-INT-004 | Manager (pembuat maupun bukan) dan staff tidak bisa setujui/tolak, baik lewat Policy maupun domain | INT | R-006 | 1 (dataset) | DEV | `userWithRole()` |
| PO.2-INT-005 | Admin lain menyetujui → disetujui (catatan opsional tersimpan); menolak dengan catatan → ditolak | INT | R-001 | 2 | DEV | Pembuat = manager, pemutus = admin |
| PO.2-INT-006 | Keputusan atas PO berstatus draft/disetujui/ditolak/diterima ditolak | INT | R-005 | 1 (dataset 4×2) | DEV | Setuju dan tolak |
| PO.2-INT-007 | Tiap transisi (buat, ajukan, setujui, tolak, terima) mencatat activity dengan causer = pelaku dan status lama/baru; percobaan yang ditolak tidak mencatat transisi | INT | R-009 | 1 (dataset 5) + 1 | DEV | Ikuti pola `AuditTrailTest` (log name, causer_id) |
| PO.3-INT-001 | Staff menerima PO disetujui: 1 movement IN per item, qty = jumlah item, merujuk PO, `user_id` = staff, catatan memuat nomor PO; `current_stock` tiap produk naik tepat sebesar qty; status diterima | INT | R-002 | 1 | DEV | Minimal 2 item, 2 produk dengan stok awal berbeda |
| PO.3-INT-002 | Menerima PO draft/diajukan/ditolak/diterima ditolak; jumlah movement dan stok tidak berubah | INT | R-002, R-005 | 1 (dataset 4) | DEV | Termasuk penerimaan ganda |
| PO.3-INT-003 | Kegagalan saat membuat movement item ke-2 → tidak ada movement tersimpan, status tetap disetujui, stok tidak berubah | INT | R-002 | 1 | DEV | Injeksi lewat listener `StockMovement::creating` yang melempar exception di test |
| PO.3-INT-004 | Movement ber-PO tidak bisa di-update atau di-delete oleh staff, manager, **dan admin**; stok tetap | INT | R-003 | 1 (dataset 3 role × 2 aksi) | DEV | Model langsung + Policy; mitigasi risiko skor 9 |
| PO.3-E2E-001 | Alur utama: manager membuat & mengajukan → admin menyetujui → staff menerima; stok produk naik sebesar qty dan movement menunjuk nomor PO | E2E | R-002, R-001, R-012 | 1 | QA | Success signal SPEC; data sekali pakai via fixture |

**Total P0:** 19 skenario (~25–40 jam)

### P1 (Tinggi)

**Kriteria:** perilaku inti, sering dipakai, atau kompleks dengan jangkauan pengguna besar dan workaround terbatas.

| ID | Requirement / Skenario | Level | Risk Link | Jumlah | Owner | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| PO.1-INT-008 | Item awal = hanya produk kritis milik supplier terpilih, harga = `harga_modal` | INT | R-008 | 1 | DEV | Supplier dengan produk kritis + tidak kritis + produk supplier lain yang kritis |
| PO.1-INT-009 | Manager boleh menambah produk tidak kritis supplier yang sama; produk supplier lain ditolak | INT | R-010 | 2 | DEV | — |
| PO.1-INT-010 | Nomor `PO-YYYYMM-####` urut dalam satu bulan dan reset di bulan baru | INT | R-007 | 1 | DEV | `$this->travelTo()` |
| PO.1-INT-011 | Nomor PO unik di DB (insert duplikat → `UniqueConstraintViolationException`) | INT | R-007 | 1 | DEV | Pengganti test konkurensi sungguhan |
| PO.1-INT-012 | Validasi item: qty ≥1, harga ≥0, produk tidak ganda dalam satu PO | INT | R-010 | 1 (dataset) | DEV | — |
| PO.1-INT-013 | Properti activity log PO tidak memuat harga/subtotal/total | INT | R-004 | 1 | DEV | Mencegah bocor lewat audit UI |
| PO.2-INT-008 | Catatan wajib saat menolak (validasi form + domain); opsional saat menyetujui | INT | R-011 | 2 | DEV | — |
| PO.3-INT-005 | UI pergerakan stok: aksi edit/hapus tersembunyi untuk movement ber-PO; buka halaman edit movement ber-PO → 403 | INT | R-003 | 2 | DEV | Livewire `ListStockMovements` / `EditStockMovement` |
| PO.3-INT-006 | Regresi: movement tanpa PO tetap bisa diedit staff dan dihapus manager | INT | R-003 | 1 | DEV | Memastikan kunci tidak terlalu lebar |
| PO.3-INT-007 | Manager juga bisa menerima; penerimaan tidak mengubah `harga_modal` produk | INT | R-006, R-002 | 1 | DEV | — |
| PO.3-E2E-002 | Staff membuka daftar & detail PO: tidak ada header/teks harga, tombol Terima tersedia di PO disetujui | E2E | R-004 | 1 | QA | Pola `authorization.spec.ts`; `pageAs('staff')` |

**Total P1:** 11 skenario (~10–18 jam)

### P2 (Menengah)

**Kriteria:** perilaku sekunder dengan jangkauan lebih sempit dan workaround yang dapat diterima.

| ID | Requirement / Skenario | Level | Risk Link | Jumlah | Owner | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| PO.1-INT-014 | Supplier tidak bisa diganti setelah draft dibuat (field disabled + ditolak di domain) | INT | R-010 | 1 | DEV | — |
| PO.1-INT-015 | Produk tanpa supplier tidak pernah muncul sebagai opsi item | INT | R-008 | 1 | DEV | `products.supplier_id` nullable |
| PO.1-INT-016 | Subtotal = qty × harga, total = jumlah subtotal, tampil `money('IDR')` untuk manager | INT | - | 1 | DEV | — |
| PO.3-INT-008 | Daftar pergerakan stok menampilkan rujukan nomor PO | INT | R-002 | 1 | DEV | Ketertelusuran |

**Total P2:** 4 skenario (~2–4 jam)

### P3 (Rendah)

**Kriteria:** jarang, kosmetik, atau eksperimental dengan dampak minimal dan workaround mudah.

| ID | Requirement / Skenario | Level | Risk Link | Jumlah | Owner | Catatan |
| --- | --- | --- | --- | --- | --- | --- |
| PO.1-INT-017 | Label Bahasa Indonesia, grup navigasi `Transaksi`, badge status | INT | - | 1 | DEV | Smoke render |
| PO.3-INT-009 | Penerimaan PO 50 item selesai dan jumlah query dalam batas wajar | INT | R-013 | 1 | DEV | Sanity, bukan load test (ambang UNKNOWN) |
| PO.1-MAN-001 | Dua manager membuat draft hampir bersamaan → nomor berbeda | MAN | R-007 | 1 | QA | Eksploratif; Pest satu proses tidak bisa mensimulasikan konkurensi sungguhan |

**Total P3:** 3 skenario (~1–3 jam)

---

## Strategi Eksekusi

**Filosofi:** jalankan semua skenario fungsional di setiap pull request selama suite tetap di bawah 15 menit; tunda hanya pekerjaan yang mahal secara infrastruktur atau durasi. Prioritas P0–P3 bukan jadwal eksekusi.

- **Pull request:** seluruh skenario Pest (INT) — cepat, database `testing`. E2E Playwright (PO.3-E2E-001/002) ikut dijalankan lokal sebelum serah terima story 3. Playwright di proyek ini **satu worker** (`artisan serve`, throttle login 5/menit), bukan paralel; dengan dua spec PO tambahan durasi tetap jauh di bawah 15 menit.
- **Nightly:** tidak ada suite khusus untuk epik ini (belum ada CI; bila CI dibuat lewat `/bmad-testarch-ci`, E2E bisa dipindah ke sini).
- **Weekly / sebelum rilis:** PO.1-MAN-001 (konkurensi nomor) secara eksploratif.

---

## Estimasi Sumber Daya

| Prioritas | Jumlah | Rentang Jam | Catatan |
| --- | --- | --- | --- |
| P0 | 19 | ~25–40 jam | Factory PO + state, injeksi kegagalan, dataset role |
| P1 | 11 | ~10–18 jam | Banyak memakai ulang factory P0 |
| P2 | 4 | ~2–4 jam | Skenario sederhana |
| P3 | 3 | ~1–3 jam | Smoke + eksploratif |
| **Total** | **37** | **~38–65 jam** | **~1–2 minggu** |

### Prasyarat

**Data test:**

- `PurchaseOrderFactory` dengan state `draft()`, `submitted()`, `approved()`, `rejected()`, `received()` dan `createdBy(User $user)`; `PurchaseOrderItemFactory` (`for($product)`, qty, harga)
- Produk kritis: `Product::factory()` dengan `current_stock` ≤ `reorder_point` dan `supplier_id` terisi (stok awal lewat `StockMovement`, bukan kolom)
- Fixture E2E baru `tests/support/fixtures/purchase-order-fixture.ts`: buat supplier + produk `E2E-<uuid>` via UI sebagai manager, digabung di `fixtures/index.ts`

**Tooling:** Pest 4 + `pest-plugin-livewire` (sudah ada), Playwright (sudah ada). `@seontechnologies/playwright-utils` tidak terpasang — tidak dipakai, tidak menambah dependensi.

**Lingkungan:** Sail berjalan; seluruh perintah via `docker compose exec -T -u sail laravel.test …`; dev DB di-seed untuk E2E.

---

## Kriteria Quality Gate

### Ambang Lulus/Gagal

- **P0:** 100% lulus (tanpa pengecualian)
- **P1:** ≥95% lulus (kegagalan butuh waiver)
- **P2/P3:** ≥90% (informatif)
- **Mitigasi risiko tinggi:** 100% selesai atau ada waiver yang disetujui; **R-003 (skor 9) tidak bisa di-waive** — gate FAIL bila PO.3-INT-004 belum hijau

### Target Cakupan

- Alur kritis (state machine + penerimaan): ≥80% skenario P0/P1 terotomasi
- Skenario keamanan (R-001, R-004, R-006): 100%
- Logika bisnis (R-005, R-008, R-010): ≥70%
- Edge case: ≥50%

### Syarat yang Tidak Bisa Ditawar

- [ ] Semua P0 lulus
- [ ] Tidak ada risiko ≥6 yang belum termitigasi
- [ ] Test SEC lulus 100%, dan larangan memutuskan sendiri dibuktikan dengan **admin sebagai pembuat**
- [ ] Bukti NFR (laporan Pest/Playwright) tersedia; status PASS/CONCERNS/FAIL final diputuskan oleh `/bmad-testarch-nfr` setelah implementasi

---

## Rencana Mitigasi

### R-003: Movement hasil PO bisa diedit/dihapus (Skor: 9)

**Strategi:** Tolak `updating`/`deleting` `StockMovement` yang punya `purchase_order_id` di level model (preseden: adjustment di `StockMovementObserver`), sehingga berlaku juga untuk admin dan jalur non-UI; selaraskan `StockMovementPolicy::update/delete` dan sembunyikan aksi di tabel/halaman edit.
**Owner:** Dev story 3 · **Timeline:** sebelum story 3 ditutup · **Status:** Planned
**Verifikasi:** PO.3-INT-004 (domain, 3 role termasuk admin), PO.3-INT-005 (UI), PO.3-INT-006 (movement non-PO tidak ikut terkunci).

### R-001: Admin pembuat memutuskan PO sendiri (Skor: 6)

**Strategi:** Method domain `approve`/`reject` menolak bila pelaku = pembuat, apa pun role-nya; Policy hanya lapis tambahan untuk menyembunyikan aksi.
**Owner:** Dev story 2 · **Timeline:** sebelum story 2 ditutup · **Status:** Planned
**Verifikasi:** PO.2-INT-001..003 dengan `adminUser()` sebagai pembuat; PO.2-INT-005 sebagai kontrol positif.

### R-002: Penerimaan tidak atomik / ganda (Skor: 6)

**Strategi:** `DB::transaction` + `lockForUpdate` PO, cek status `disetujui` di dalam transaksi, buat movement lewat `StockMovement::create()`, lalu set status diterima.
**Owner:** Dev story 3 · **Timeline:** sebelum story 3 ditutup · **Status:** Planned
**Verifikasi:** PO.3-INT-001..003, PO.3-E2E-001.

### R-004: Harga modal bocor ke staff (Skor: 6)

**Strategi:** Gate `can('viewCost', Product::class)` pada setiap field repeater, kolom tabel, entri detail, dan ringkasan total; `logOnly` activity PO tanpa harga.
**Owner:** Dev story 1 · **Timeline:** sebelum story 1 ditutup · **Status:** Planned
**Verifikasi:** PO.1-INT-004/005, PO.1-INT-013, PO.3-E2E-002.

### R-005: Transisi status ilegal (Skor: 6)

**Strategi:** Satu peta transisi di domain (enum status + method transisi) yang menolak transisi di luar `state-machines.md`; edit/hapus PO hanya untuk draft.
**Owner:** Dev story 1–3 · **Status:** Planned
**Verifikasi:** PO.1-INT-006/007, PO.2-INT-006, PO.3-INT-002.

### R-006: Matriks role PO salah (Skor: 6)

**Strategi:** Tambah `PurchaseOrder` ke `RoleSeeder` (manager: CRUD domain; staff: `ViewAny`/`View` + ability terima), ability kustom `approve`/`reject`/`receive` di Policy.
**Owner:** Dev story 1–2 · **Status:** Planned
**Verifikasi:** PO.1-INT-001..003, PO.2-INT-004, PO.3-INT-007.

### R-012: Residu data E2E di database dev (Skor: 6)

**Strategi:** Fixture membuat supplier + produk sekali pakai per test; draft PO yang gagal dihapus fixture; movement/PO diterima dibiarkan sebagai residu record `E2E-*` (tidak bisa dihapus by design). Reset penuh dev DB dengan `migrate:fresh --seed` bila residu mengganggu.
**Owner:** QA story 3 · **Status:** Planned
**Verifikasi:** PO.3-E2E-001 lulus berulang tanpa bergantung pada jumlah baris data demo.

---

## Asumsi dan Dependensi

### Asumsi (diambil sebagai opsi paling sederhana yang konsisten dengan kode)

1. **A-1 — Entry point domain:** transisi ditulis sebagai method di model `PurchaseOrder` (`submit(User $actor)`, `approve(User $actor, ?string $note)`, `reject(User $actor, string $note)`, `receive(User $actor)`), bukan folder `app/Actions` baru (AGENTS/CLAUDE: jangan buat folder dasar baru tanpa persetujuan). ATDD menulis test terhadap kontrak ini; bila dev memilih nama lain, test disesuaikan di story 1.
2. **A-2 — Exception:** pelanggaran pelaku (pembuat memutuskan sendiri, role tidak berwenang) melempar `Illuminate\Auth\Access\AuthorizationException` (preseden `StockMovementObserver`); transisi status ilegal melempar `DomainException`. Test selalu juga menegaskan state DB tidak berubah.
3. **A-3 — Enum status:** `App\Enums\PurchaseOrderStatus` dengan case `Draft`, `Submitted`, `Approved`, `Rejected`, `Received` (TitleCase), label Indonesia draft/diajukan/disetujui/ditolak/diterima.
4. **A-4 — Rujukan movement:** kolom baru nullable `stock_movements.purchase_order_id` (FK) lewat migrasi baru dengan `down()`.
5. **A-5 — Pembuat:** kolom `created_by` pada PO, diisi user login saat draft dibuat.
6. **A-6 — Residu E2E:** karena movement ber-PO tidak bisa dihapus, residu record sekali pakai `E2E-*` di dev DB diterima; produk demo tidak pernah disentuh E2E.

### Dependensi

1. Story 1 (model, enum, factory, permission) — dibutuhkan semua test story 2 dan 3
2. Story 2 (persetujuan) — dibutuhkan PO.3-INT-* yang memakai state `approved()` hanya lewat factory, jadi test story 3 bisa ditulis paralel; E2E butuh story 1–3 lengkap

### Risiko terhadap Rencana

- **Risiko:** test otorisasi memakai `adminUser()` dan lolos palsu karena bypass Shield.
  - **Dampak:** R-001/R-006 tampak termitigasi padahal tidak.
  - **Kontingensi:** aturan review — setiap test pembatasan memakai `userWithRole()`; admin hanya dipakai sebagai **pembuat** (R-001) atau sebagai kontrol positif.
- **Risiko:** throttle login Filament 5/menit saat E2E memakai 3 role.
  - **Kontingensi:** pakai sesi tersimpan dari `auth.setup.ts`; `php artisan cache:clear` bila ter-throttle.

---

## Panduan untuk `/bmad-testarch-atdd` (red phase)

Tujuan: scaffold **gagal** untuk seluruh P0 sebelum implementasi, per story.

| Berkas test (baru/ubah) | Skenario | Story |
| --- | --- | --- |
| `tests/Feature/AuthorizationMatrixTest.php` (ubah: tambah baris dataset) | PO.1-INT-002 | 1 |
| `tests/Feature/PurchaseOrderDraftTest.php` | PO.1-INT-001, -003, -006, -007 | 1 |
| `tests/Feature/CostMaskingTest.php` (ubah: tambah test PO) | PO.1-INT-004, -005 | 1 |
| `tests/Feature/PurchaseOrderApprovalTest.php` | PO.2-INT-001..006 | 2 |
| `tests/Feature/AuditTrailTest.php` (ubah: tambah test PO) | PO.2-INT-007 | 1–3 |
| `tests/Feature/PurchaseOrderReceiptTest.php` | PO.3-INT-001..004 | 3 |
| `tests/e2e/purchase-order.spec.ts` + `tests/support/fixtures/purchase-order-fixture.ts` | PO.3-E2E-001 | 3 |

Aturan:

- Buat dengan `docker compose exec -T -u sail laravel.test php artisan make:test --pest <Nama>`; letakkan di `tests/Feature/`.
- Nama test bahasa Inggris deskriptif seperti test yang ada (mis. `it('rejects approval by the admin who created the purchase order')`); tanpa komentar di test (guideline Spatie).
- Akses dibuktikan dengan `userWithRole('staff'|'manager')`. `adminUser()` hanya untuk peran **pembuat** di PO.2-INT-001..003 dan sebagai pemutus sah di PO.2-INT-005.
- Setiap test pelanggaran menegaskan dua hal: exception (A-2) **dan** state DB tidak berubah (status, jumlah movement, `current_stock`, jumlah activity).
- Stok awal produk dibuat lewat `StockMovement` factory, bukan menulis `current_stock`.
- Masking: gunakan harga unik (mis. 123457) agar `assertDontSee` bermakna; selalu pasangkan dengan test positif manager.
- Red phase yang benar gagal karena kelas/method belum ada atau perilaku belum benar — bukan karena salah setup.

## Panduan untuk `/bmad-testarch-automate` (setelah implementasi)

- Perluas ke P1–P3 (PO.1-INT-008..017, PO.2-INT-008, PO.3-INT-005..009, PO.3-E2E-002) memakai factory dari ATDD.
- Livewire Filament: aksi tabel/halaman diuji dengan `assertActionHidden`/`assertActionVisible` + `callAction`, lalu selalu cek DB (UI tersembunyi bukan bukti).
- E2E: impor `test`/`expect` dari `tests/support/fixtures`; satu worker; tunggu request Livewire selesai sebelum assert (pola `category.spec.ts`); selector berbasis label Indonesia (`getByLabel`, `getByRole`).
- Regresi wajib tetap hijau: `StockMovementTest`, `HardeningTest`, `AuthorizationMatrixTest`, `CostMaskingTest`, `AuditTrailTest`, `tests/e2e/authorization.spec.ts`.
- Setelah selesai, minta user menjalankan suite penuh: `docker compose exec -T -u sail laravel.test php artisan test --compact` dan `npm run test:e2e`.

---

## Interworking & Regresi

| Komponen | Dampak | Cakupan Regresi |
| --- | --- | --- |
| `StockMovement` + `StockMovementObserver` | Kunci update/delete untuk movement ber-PO; movement IN baru dari penerimaan | `StockMovementTest`, `HardeningTest`, PO.3-INT-006 |
| `StockMovementPolicy` / `StockMovementResource` | Aksi edit/hapus bersyarat | PO.3-INT-005/006, `AuthorizationMatrixTest` |
| `RoleSeeder` / `ShieldSeeder` | Resource baru `PurchaseOrder` | `AuthorizationMatrixTest`, `RolePermissionTest` |
| `ProductPolicy::viewCost` | Dipakai ulang untuk harga PO | `CostMaskingTest`, `tests/e2e/authorization.spec.ts` |
| activitylog | Log name baru untuk PO | `AuditTrailTest` |

---

## Langkah Lanjutan (Manual)

- Jalankan `/bmad-testarch-atdd` untuk membuat test P0 yang gagal (workflow terpisah, tidak dijalankan otomatis).
- Jalankan `/bmad-testarch-automate` untuk cakupan P1–P3 setelah implementasi ada.
- Setelah implementasi: `/bmad-testarch-nfr` (bukti NFR) dan `/bmad-testarch-trace` (matriks ketertelusuran + gate).

## Persetujuan

- [ ] Product Manager: ______ Tanggal: ______
- [ ] Tech Lead: ______ Tanggal: ______
- [ ] QA Lead: ______ Tanggal: ______

---

## Lampiran

### Referensi Knowledge Base

- `risk-governance.md` — kerangka klasifikasi risiko & gate
- `probability-impact.md` — skala skor dan ambang aksi
- `test-levels-framework.md` — pemilihan level test, format ID
- `test-priorities-matrix.md` — kriteria P0–P3
- `nfr-criteria.md` — perencanaan bukti NFR

### Dokumen Terkait

- Spec: `_bmad-output/specs/spec-purchase-order/SPEC.md`
- Stories: `_bmad-output/specs/spec-purchase-order/stories.yaml`
- State machine: `_bmad-output/specs/spec-purchase-order/state-machines.md`
- Brownfield: `_bmad-output/specs/spec-purchase-order/brownfield.md`
- Konvensi proyek: `AGENTS.md`, `tests/README.md`

---

**Dihasilkan oleh:** BMad TEA Agent — Test Architect Module · **Workflow:** `bmad-testarch-test-design` · **Versi:** 4.0 (BMad v6)
