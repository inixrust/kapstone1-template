---
id: SPEC-purchase-order
companions:
  - brownfield.md
  - state-machines.md
  - ../../../AGENTS.md
sources: []
---

> **Canonical contract.** This SPEC and the files in `companions:` are the complete, preservation-validated contract for what to build, test, and validate. Source documents listed in frontmatter are for traceability — consult them only if you need narrative rationale or prose color this contract intentionally omits.

# Purchase Order ke Supplier

## Why

Pain operasional: manager sering terlambat memesan ulang produk berstatus kritis karena pesanan dibuat di luar sistem, dan barang yang datang dicatat manual sebagai pergerakan stok sehingga tidak tertelusur ke pesanannya. Tujuannya: pesanan ulang tercatat di aplikasi, disetujui oleh orang yang berbeda dari pembuatnya, dan barang masuk otomatis menjadi pergerakan stok yang bisa ditelusuri ke PO.

## Capabilities

- **CAP-1** — Buat draft PO dari produk kritis
  - **intent:** Manager memilih satu supplier, sistem menawarkan produk kritis milik supplier itu sebagai item awal, manager mengisi jumlah (dan boleh mengubah harga) per item, lalu mengajukan PO.
  - **success:** Manager membuat draft untuk supplier dengan produk kritis dan tidak kritis; item awal hanya produk kritis supplier itu dengan harga = `harga_modal` produk; PO bernomor `PO-YYYYMM-####` (urut per bulan) dan berstatus draft; setelah diajukan berstatus diajukan. Staff tidak bisa membuat atau mengajukan PO.

- **CAP-2** — Persetujuan PO
  - **intent:** Admin menyetujui atau menolak PO berstatus diajukan, disertai catatan; pembuat PO tidak dapat memutuskan PO buatannya sendiri.
  - **success:** Admin lain menyetujui → disetujui, atau menolak dengan catatan → ditolak; keputusan oleh pembuat PO (termasuk admin pembuat) ditolak sistem; manager dan staff tidak bisa memutuskan; keputusan atas PO yang bukan diajukan ditolak.

- **CAP-3** — Penerimaan barang
  - **intent:** Manager atau staff mencatat penerimaan PO yang disetujui; setiap item menjadi pergerakan stok masuk (IN) yang merujuk PO, dan PO menjadi diterima.
  - **success:** Staff menerima PO disetujui → satu movement IN per item dengan jumlah = jumlah item, merujuk PO, `user_id` = penerima; `current_stock` tiap produk bertambah sebesar jumlah itu; PO berstatus diterima; menerima PO yang bukan disetujui (termasuk yang sudah diterima) ditolak tanpa movement baru.

## Constraints

- Status PO: draft → diajukan → disetujui | ditolak; disetujui → diterima. Tidak ada transisi lain (lihat `state-machines.md`).
- PO berstatus diterima atau ditolak tidak dapat diubah atau dihapus.
- Satu PO = satu supplier; item hanya produk milik supplier PO.
- Stok hanya berubah lewat `StockMovement` yang dibuat lewat model (Observer me-replay); `current_stock` tidak ditulis langsung.
- Harga modal pada PO (harga per item, subtotal, total) tersembunyi total untuk staff — digate `can('viewCost', Product::class)`, bukan `****`.
- Setiap perubahan status PO tercatat di audit trail (activitylog) beserta pelakunya (causer).
- Larangan memutuskan PO sendiri tidak boleh bergantung hanya pada Policy: Shield `intercept_gate = before` membuat admin lolos semua ability (lihat `brownfield.md`).
- Hak akses mengikuti pola Filament Shield (`Action:PurchaseOrder`) plus ability kustom di Policy; role: admin, manager, staff. Resource baru masuk `RoleSeeder` dan `AuthorizationMatrixTest`.
- Hanya admin yang boleh menyetujui dan menolak.
- Teks antarmuka Bahasa Indonesia lewat `->label('...')`, konsisten dengan resource lain.
- Migrasi lama tidak diubah; skema baru lewat migrasi baru (dengan `down()`).
- Mata uang Rupiah saja (`money('IDR')`, prefix `Rp`).

## Non-goals

- Penerimaan sebagian.
- Mengirim PO ke supplier (email/PDF).
- Mata uang selain Rupiah.
- Multi-gudang.
- Pembayaran ke supplier.
- Notifikasi (email, database, atau in-app) pada perubahan status.
- Perubahan pada fitur yang sudah ada di luar kebutuhan epik ini.

## Success signal

- Demo dengan akun demo: `manager@demo.com` membuat dan mengajukan PO → `admin@demo.com` menyetujui → `staff@demo.com` mencatat penerimaan, dan stok tiap produk bertambah tepat sebesar jumlah di PO, dengan movement yang menunjuk ke PO itu.
- Seluruh test Pest lama tetap hijau, test baru untuk ketiga story hijau, dan E2E Playwright alur utama (manager → admin → staff) hijau.

## Assumptions

- Produk kritis = `Product::withStockStatus('critical')` (`current_stock <= reorder_point`), definisi yang sudah ada.
- Pembuat dan pengaju PO adalah manager (admin juga, lewat bypass Shield); staff tidak membuat atau mengajukan PO.
- Hanya PO draft yang itemnya bisa diubah; draft boleh dihapus manager. Diajukan dan disetujui hanya bisa bertransisi.
- Supplier PO tidak bisa diganti setelah draft dibuat; tiap produk muncul sekali per PO; jumlah ≥ 1; harga ≥ 0.
- PO tanpa item tidak bisa diajukan.
- Nomor PO diberikan saat draft dibuat dan unik.
- Larangan pembuat memutuskan PO sendiri berlaku untuk setuju dan tolak.
- Catatan wajib saat menolak, opsional saat menyetujui.
- Penerimaan selalu penuh dan atomik (semua movement + status dalam satu transaksi); tidak mengubah `harga_modal` produk.
- Movement yang merujuk PO tidak dapat diubah atau dihapus, agar jejak ke PO tetap utuh.
- Staff melihat daftar/detail PO (tanpa harga) agar bisa menerima.
- `user_id` movement = user yang mencatat penerimaan; catatan movement memuat nomor PO.
