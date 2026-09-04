# EXERCISE m2.2 — Role & Permission dengan Filament Shield

## Tujuan Pembelajaran
- Menerapkan RBAC (role-based access control) pada panel Filament memakai
  `spatie/laravel-permission` + `bezhansalleh/filament-shield`.

## Kondisi Codebase Saat Ini
- 3 akun demo tersedia (belum punya role).
- Shield terpasang tapi belum dikonfigurasi di panel.

## Yang Perlu Dibangun
1. Registrasi `FilamentShieldPlugin` di `AdminPanelProvider`.
2. `shield:install admin` / `shield:generate` untuk membuat policy & permission
   per resource. Jika command interaktif macet, pakai eskalasi (flag
   `--no-interaction`, pipe jawaban, atau fallback Seeder manual).
3. 3 role berjenjang lewat `RoleSeeder`:
   - `admin`  → super admin (semua permission).
   - `manager`→ semua kecuali hapus permanen data master + kelola role.
   - `staff`  → hanya view + create/update Product & StockMovement dasar,
     TANPA delete.
4. Assign role ke akun demo (`admin@`→admin, dst.).

## Acceptance Criteria
- Test: staff mendapat 403 saat mencoba delete.
- Manual walkthrough: menu/aksi sesuai role.
- `DECISIONS.md` mencatat apakah `shield:*` berjalan langsung atau perlu
  fallback.
