<!-- bmad:context -->
<!-- Verified 2026-11-08 against 8ddfceb. Managed by bmad-project-context; edits inside this block are replaced on refresh. Keep anything you want preserved outside the markers. -->

## inventory (Inixindo Bandung)

Inventory management satu gudang: Laravel 13 + Filament 5 (Livewire, tanpa REST API), Spatie Permission via Filament Shield, activitylog, Pest 4, MySQL 8.4 di Laravel Sail. Keputusan teknis ada di `DECISIONS.md`, ringkasan fitur di `PROGRESS.md`, artefak BMad di `_bmad-output/`. Guideline Laravel Boost ada di `CLAUDE.md`; aturan di blok ini mengalahkannya bila bertentangan.

## Policy

- Sebelum menulis kode PHP, baca @docs/laravel-php-guidelines.md (guideline Spatie); pengecualiannya ada di "Conventions" di bawah.
- Semua form pakai CSRF token (default Laravel, jangan dimatikan).
- Aksi destruktif (delete/update) pakai method HTTP DELETE/PUT, bukan GET.
- Setiap fitur dengan otorisasi WAJIB punya automated test yang membuktikan hanya user berwenang yang bisa mengaksesnya.
- Password di-hash (default Laravel).
- Nilai sensitif yang disimpan di database (API key/secret, kalau ada) harus dienkripsi, bukan plaintext.
- Jangan pernah memasang PHP/Composer di host; semua runtime lewat container Sail.
- Jangan mengubah migrasi yang sudah ada; buat migrasi baru.

## Where things are

- Agregasi dashboard: `app/Services/InventoryAnalyticsService.php`; widget hanya memanggil service ini.
- Perhitungan stok: `app/Observers/StockMovementObserver.php`; aturan akses di `app/Policies/`.
- Role → permission: `database/seeders/RoleSeeder.php`; permission Shield (`Action:Resource`) dibuat ulang oleh `ShieldSeeder`.
- Helper test (`userWithRole()`, `adminUser()`): `tests/Pest.php`.
- Skill Laravel Boost: `.claude/skills/` (mis. `laravel-best-practices`, `testing-best-practices`).

## Running and verifying

- Jalankan setiap perintah `php`, `artisan`, `composer`, `vendor/bin/*` sebagai `docker compose exec -T -u sail laravel.test <perintah>` — termasuk semua perintah polos di guideline Boost `CLAUDE.md`.
- Jangan pernah menghilangkan `-u sail`: perintah sebagai root membuat berkas cache milik root dan server web lalu error 500 `touch(): Utime failed`.
- Test: `docker compose exec -T -u sail laravel.test php artisan test --compact [path|--filter=...]`; memakai database MySQL `testing` (phpunit.xml), bukan database dev.
- Format: `docker compose exec -T -u sail laravel.test vendor/bin/pint --dirty --format agent`.
- E2E Playwright jalan di host (Node, bukan container) terhadap app Sail yang sudah di-seed: `npm run test:e2e`; memakai database dev, satu worker, dan throttle login Filament 5/menit — detail di `tests/README.md`.
- Clone baru belum punya `vendor/`, dan `compose.yaml` mem-build image dari `vendor/laravel/sail/runtimes/8.5` — jalankan `composer install` lewat container Composer Sail dulu (README, langkah Setup), bukan Composer host.

## Conventions that differ from defaults

- Taruh test baru di `tests/Feature/`, bukan mirror `app/` seperti saran Spatie — `tests/Pest.php` hanya memasang `TestCase` + `RefreshDatabase` di `Feature`, dan `phpunit.xml` hanya menjalankan `tests/Feature` dan `tests/Unit`.
- Migrasi baru tetap menulis `down()`, mengikuti migrasi yang ada (penyimpangan sadar dari Spatie, lihat `PROGRESS.md`).
- `current_stock` hanya berubah lewat record `StockMovement` (Observer me-replay semua movement); jangan menulisnya langsung atau menambahkannya ke `$fillable`.
- `adjustment` hanya untuk manager/admin dan ditegakkan di Policy + Observer; jalur baru yang mengubah stok harus membuat `StockMovement` lewat model, bukan query builder, agar Observer tetap berlaku.
- Harga modal (dan nilai turunannya seperti nilai inventory) harus tersembunyi total untuk staff, bukan `****`: gate setiap field, kolom, widget, atau ekspor baru dengan `can('viewCost', Product::class)`.
- Resource Filament baru: tambahkan ke daftar resource di `RoleSeeder` dan ke test matriks otorisasi.
- Test otorisasi memakai `userWithRole('staff')`/`userWithRole('manager')`; `adminUser()` melewati semua policy (Shield `intercept_gate = before`), jadi tidak membuktikan apa pun soal akses.
- Teks antarmuka berbahasa Indonesia, ditulis langsung lewat `->label('...')` seperti resource lain (tidak memakai berkas `lang/`).

## Known pitfalls

- Bila `docker` tidak ditemukan di WSL, minta user mengaktifkan WSL integration di Docker Desktop; jangan beralih ke PHP host. MCP Laravel Boost juga gagal di host karena `php` tidak ada di PATH.

<!-- /bmad:context -->
