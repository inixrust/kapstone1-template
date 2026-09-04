# DECISIONS.md — Catatan Keputusan Teknis

Dokumen ini mencatat keputusan teknis, penyesuaian mandiri, dan penanganan
command interaktif selama pembangunan aplikasi Inventory Management.

## Lingkungan & Infrastruktur

- **Runtime: Laravel Sail + Docker Desktop.** Docker Desktop tersedia dan
  berjalan (linux containers), jadi TIDAK memakai fallback SQLite. Database
  aktif adalah **MySQL 8.4** dalam container Docker (service `mysql`).
- **Laravel 13.30.1** (PHP `^8.3`). Image Sail runtime memakai **PHP 8.5**
  (`sail-8.5/app`), host CLI juga PHP 8.5 (Herd) + Composer 2.8.
- **Scaffolding vs runtime:** `composer create-project`, `composer require
  laravel/sail`, dan `sail:install` dijalankan dengan Composer/PHP host.
  Seluruh langkah runtime (composer require lain, artisan, migrasi, test,
  serve) dijalankan **di dalam container** (`docker compose exec -T
  laravel.test ...`) supaya konsisten dan benar-benar memakai Docker Desktop.
- **`ext-intl` tidak ada di PHP host (Herd)** → Filament tidak bisa di-install
  via Composer host. Ini alasan tambahan menjalankan `composer require`
  Filament & paket lain di dalam container (image Sail sudah menyertakan intl).
- **File compose bernama `compose.yaml`** (bukan `docker-compose.yml`) — nama
  baru default yang dihasilkan `sail:install` pada Laravel 13.
- **Port:** `APP_PORT=8000` (host → container 80), `FORWARD_DB_PORT=3307`
  (menghindari bentrok MySQL lokal). `WWWGROUP=1000`/`WWWUSER=1000` di-set di
  `.env` agar build image Sail sukses saat dijalankan dari Git Bash di Windows.

## Brand & Theme (m0.2)

- Palet: `primary #143564` (navy), `danger #EA2339`, `info #5CAAD5`,
  `success #1F9254`. **`success` di luar brand asset resmi** — dipilih hijau
  standar yang kontras baik dengan navy untuk badge "stok aman".
- **Font: default Filament (Inter).** Tidak override ke Poppins agar tidak
  menambah dependency font & menjaga performa; cukup untuk kebutuhan brand.
- Logo brand portrait (420x504) dipakai apa adanya via `brandLogo()` dengan
  `brandLogoHeight('2.5rem')`.

## Versi Paket

- **Filament 5** (`filament/filament` v5.7.x) — sesuai spesifikasi. (Filament 4
  juga tersedia, tapi task meminta 5.)

## Coding Standards — Jalur yang Dipakai

- **Laravel Boost (jalur resmi, BERHASIL):** `composer require laravel/boost
  --dev` lalu `php artisan boost:install --guidelines --skills
  --no-interaction`. Ternyata `boost:install` MENDUKUNG `--no-interaction` +
  flag `--guidelines`/`--skills`, jadi tidak perlu penanganan interaktif.
  - 7 guidelines ditulis ke `CLAUDE.md` (foundation, boost, php, deployments,
    laravel/core, pint/core, pest/core).
  - 4 skills disinkron ke `.claude/skills/` — termasuk **`laravel-best-practices`**
    (skill resmi Laravel dari package `laravel/boost`), `testing-best-practices`,
    `infer-conventions`, `tailwindcss-development`. Jadi tidak perlu
    `boost:add-skill` manual: skill sudah otomatis ter-include lewat
    `boost:install --skills`.
- **Guideline Spatie (fallback download, dipakai sebagai pelengkap):** package
  `spatie/guidelines-skills` gagal diverifikasi via Composer (timeout packagist
  sesaat), jadi memakai fallback resmi Spatie:
  `curl -o docs/laravel-php-guidelines.md https://spatie.be/laravel-php-ai-guidelines.md`
  (309 baris) + menambahkan section "Coding Standards (Spatie)" berisi prinsip
  keamanan (CSRF, method HTTP destruktif, test otorisasi, hash password,
  enkripsi nilai sensitif) ke `CLAUDE.md`.

## Akun Demo (m2.1)

Dibuat lewat `UserSeeder` (idempotent `updateOrCreate` by email), BUKAN
`make:filament-user` (interaktif). Password sama untuk semua: **`password`**.

| Email | Nama | Role (m2.2) |
|-------|------|-------------|
| `staff@demo.com` | Staff Gudang | staff |
| `manager@demo.com` | Manager Inventory | manager |
| `admin@demo.com` | Administrator | admin |

## Filament Shield / RBAC (m2.2)

- **`shield:setup` versi lama TIDAK dipakai.** Filament Shield v4 memakai
  `shield:install {panel}` + `shield:generate`. Keduanya **berjalan mulus dengan
  `--no-interaction`** — tidak macet, jadi tidak butuh pipe/`yes`.
  - `php artisan shield:install admin --no-interaction` → mendaftarkan
    `FilamentShieldPlugin` ke `AdminPanelProvider` otomatis.
  - `php artisan shield:generate --all --panel=admin --no-interaction` →
    membuat 4 Policy (Category/Product/Supplier/Role) + 48 permission.
- **Format permission Shield v4: `Action:Resource`** (mis. `ViewAny:Product`,
  `Delete:Product`, `ForceDelete:Product`).
- **Super admin = role `admin`** (config `super_admin.name` diubah dari
  `super_admin` → `admin`, `intercept_gate = before`). `panel_user` dimatikan
  agar role tepat 3: `staff`, `manager`, `admin`.
- **Seeding self-contained.** `migrate:fresh` mengosongkan tabel permission,
  jadi permission di-regenerate saat seeding lewat `ShieldSeeder`
  (`Artisan::call('shield:generate', ['--option' => 'permissions'])` — mode
  `permissions` tidak menulis ulang file policy, cepat, dan otomatis mengikuti
  resource baru seperti StockMovement di m3.1). `RoleSeeder` lalu memberi subset
  permission per role & assign ke akun demo.
- **Jenjang permission:** admin=48 (semua), manager=30 (kelola penuh domain
  Product/Category/Supplier KECUALI ForceDelete* & Role), staff=8 (Product
  view/create/update + Category/Supplier read-only, TANPA delete).
- **Dampak ke test lama:** setelah policy aktif, test resource m1.1/m1.2 memakai
  helper `adminUser()` (super admin) di `tests/Pest.php`.

## Hardening & Security (m5.1)

- **Mass assignment:** semua model memakai `$fillable` eksplisit.
  `Product.current_stock` sengaja di luar `$fillable` → hanya berubah lewat
  `StockMovementObserver`. Terbukti lewat test (`fill`/`update` mengabaikan
  `current_stock`).
- **Otorisasi ditegakkan di level Model, bukan hanya UI:** adjustment stok
  diblokir untuk staff via Observer + Policy; staff tidak bisa delete produk
  (Policy). Diuji lewat manipulasi langsung ke Model.
- **Rate limiting login:** bawaan Filament aktif (`$this->rateLimit(5)` di
  halaman Login) — percobaan gagal ke-6 kena throttle. Diverifikasi test.
- **CSRF:** tidak dinonaktifkan di form mana pun (middleware
  `PreventRequestForgery` tetap aktif di panel). Sesuai guideline security
  Spatie: aksi destruktif lewat Livewire ditegakkan Policy, password di-hash
  (cast `hashed`), tidak ada nilai sensitif plaintext.

## Command Interaktif (ditangani)

- `laravel new` **tidak dipakai** (interaktif) — memakai
  `composer create-project laravel/laravel` sesuai instruksi.
- `git config` identitas sudah ter-set global (`inixrust`), commit tidak macet.
- `sail:install --with=mysql --no-interaction` dipakai agar tidak jatuh ke
  prompt pemilihan service.
- (Diisi lebih lanjut untuk `shield:setup`, `boost:install`, dll. saat
  dijalankan.)
