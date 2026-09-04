# EXERCISE m2.1 — Auth & Branding Login

## Tujuan Pembelajaran
- Memanfaatkan halaman login bawaan Filament yang sudah ber-brand.
- Menyediakan akun demo lewat Seeder (bukan `make:filament-user` yang interaktif).

## Kondisi Codebase Saat Ini
- Panel `/admin` ber-brand aktif; login page bawaan Filament.
- Belum ada akun demo.

## Yang Perlu Dibangun
1. Seeder membuat 3 user demo (idempotent, `updateOrCreate` by email):
   - `staff@demo.com`
   - `manager@demo.com`
   - `admin@demo.com`
   Password sama untuk semua (didokumentasikan di README/DECISIONS).
2. Panggil seeder dari `DatabaseSeeder`.

## Acceptance Criteria
- Test login ketiga akun berhasil (autentikasi via halaman login Filament).
- Manual walkthrough branding login benar (logo & warna brand).
