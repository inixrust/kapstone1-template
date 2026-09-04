# EXERCISE m4.1 — Masking Harga Modal

## Tujuan Pembelajaran
- Menyembunyikan data sensitif (harga modal) dari role tertentu di level UI
  Filament — hidden total, bukan obfuscation `****`.

## Kondisi Codebase Saat Ini
- ProductResource menampilkan `harga_modal` di form & table untuk semua role.

## Yang Perlu Dibangun
1. `harga_modal` di `ProductForm` dan `ProductsTable` `->visible()` hanya untuk
   manager/admin. Untuk staff, elemennya HILANG total (tidak ada di DOM),
   bukan disamarkan.
2. Beri komentar alasan di kode.
3. (Opsional) tambahkan ability `viewCost` di `ProductPolicy`.

## Acceptance Criteria
- Test: elemen form/kolom `harga_modal` tidak ada sama sekali untuk staff,
  tetap ada untuk manager/admin.
