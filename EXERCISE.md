# EXERCISE m0.1 — Backend Bootstrap

## Tujuan Pembelajaran
- Menyiapkan project Laravel 13 sebagai fondasi aplikasi Filament.
- Menjalankan stack lewat **Laravel Sail + MySQL 8 di Docker Desktop**.
- Memasang paket inti: Filament 5, spatie/laravel-permission + filament-shield,
  spatie/laravel-activitylog, Pest.
- Menyiapkan coding standards (Laravel Boost / guideline Spatie).

## Kondisi Codebase Saat Ini
- Skeleton Laravel 13 bersih hasil `composer create-project`.
- Brand assets sudah ada di `public/images/brand/` (`logo.png`, `footer.png`).
- Belum ada Filament, belum ada Sail, belum ada paket spatie, belum ada Pest.
- Database default masih SQLite.

## Yang Perlu Dibangun
1. Install Laravel Sail (`--dev`) dan generate `docker-compose.yml` dengan MySQL 8
   (`php artisan sail:install --with=mysql --no-interaction`).
2. Jalankan stack di Docker Desktop (`docker compose up -d`).
3. Install Filament 5 (`composer require filament/filament -W` +
   `php artisan filament:install --panels`).
4. Install `spatie/laravel-permission` + `bezhansalleh/filament-shield`
   (publish & migrate).
5. Install `spatie/laravel-activitylog` (publish & migrate).
6. Setup Pest (`pestphp/pest` + `php artisan pest:install`).
7. Setup coding standards (Laravel Boost / fallback guideline Spatie) dan
   dokumentasikan jalur yang dipakai di `DECISIONS.md`.

## Acceptance Criteria
- `docker compose up -d` sukses, `/admin` bisa diakses.
- `php artisan test` lulus.
- Guideline Spatie + skill Laravel disiapkan; `DECISIONS.md` mencatat jalurnya.
- Database aktif adalah **MySQL** (bukan SQLite), berjalan di Docker Desktop.
