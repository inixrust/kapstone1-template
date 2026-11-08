# Inventory Management — Inixindo Bandung

Aplikasi **Inventory Management** (single warehouse) berbasis **Laravel 13 +
Filament 5**, dengan otorisasi role-based (Laravel Policy + `spatie/laravel-permission`
via Filament Shield), masking data sensitif (harga modal), audit trail koreksi
stok (`spatie/laravel-activitylog`), dan **dashboard analytics** memakai widget
Filament native (Chart.js) — semua ber-brand **Inixindo Bandung**.

Aplikasi ini sekaligus **starter kit pelatihan**: setiap fitur punya sepasang
branch `starter/mX.Y-*` (soal + `EXERCISE.md`) dan `solution/mX.Y-*` (jawaban
teruji). Lihat [Peta Branch](#peta-branch).

---

## Arsitektur (Penting)

- **Fullstack di atas Filament 5** (panel/admin berbasis Livewire) — **tidak ada
  REST API terpisah, tidak ada Swagger.** Filament merender UI lewat Livewire.
- **CRUD lewat Filament Resource** (Form Builder & Table Builder), bukan
  komponen Livewire manual.
- **Dashboard memakai `Filament\Widgets\ChartWidget`** yang di baliknya memakai
  **Chart.js bawaan Filament** — tidak ada package chart tambahan.
- **Semua logika agregasi dashboard terpusat** di
  [`app/Services/InventoryAnalyticsService.php`](app/Services/InventoryAnalyticsService.php).
  Widget hanya memanggil service (dipersiapkan untuk integrasi Laravel AI SDK,
  lihat [Next Steps](#next-steps-integrasi-laravel-ai-sdk--rekomendasi)).

## Tech Stack

| Komponen | Versi/Catatan |
|----------|---------------|
| Laravel | 13 (PHP 8.3+) |
| Filament | 5 |
| Database | **MySQL 8** via **Laravel Sail (Docker Desktop)** |
| RBAC | `spatie/laravel-permission` + `bezhansalleh/filament-shield` |
| Audit trail | `spatie/laravel-activitylog` |
| Testing | Pest 4 |
| Coding standards | Laravel Boost (guidelines + skills) + guideline Spatie |

---

## Setup (Docker Desktop / Laravel Sail)

Prasyarat: **Docker Desktop** aktif.

```bash
# 1. Salin env
cp .env.example .env

# 2. Pasang dependency PHP lewat container (vendor/ belum ada setelah clone,
#    padahal compose.yaml mem-build image dari vendor/laravel/sail)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 3. Bangun & jalankan container (app + MySQL 8)
docker compose up -d --build

# 4. Generate app key, migrasi, dan seed data demo (selalu sebagai user sail)
docker compose exec -u sail laravel.test php artisan key:generate
docker compose exec -u sail laravel.test php artisan migrate --seed
```

Akses panel: **http://localhost:8000/admin**

> Catatan: file compose bernama `compose.yaml` (default Laravel 13). Port app
> `8000`, MySQL diteruskan ke host di `3307` (`FORWARD_DB_PORT`). Jalankan semua
> perintah artisan/composer di dalam container:
> `docker compose exec -u sail laravel.test <perintah>`.

### Akun Demo

Seluruh akun memakai password: **`password`**

| Email | Role | Hak akses ringkas |
|-------|------|-------------------|
| `admin@demo.com` | admin | Super admin — semua akses, kelola role |
| `manager@demo.com` | manager | Kelola master data & stok, adjustment, lihat harga modal; tanpa hapus permanen & kelola role |
| `staff@demo.com` | staff | Lihat + input Produk/Pergerakan stok (in/out), tanpa delete, tanpa adjustment, tanpa harga modal |

---

## Menjalankan Test

```bash
docker compose exec -u sail laravel.test php artisan test
```

Test memakai database MySQL `testing` (dibuat otomatis oleh Sail). Mencakup CRUD
resource, matriks otorisasi (staff/manager/admin × resource), masking harga
modal, audit trail, penegakan adjustment di level Model, dan seluruh widget
dashboard.

---

## Fitur Utama

- **Master Data**: Produk, Kategori, Supplier (+ RelationManager produk per
  supplier).
- **Pergerakan Stok**: tipe `in`/`out`/`adjustment`. `current_stock` di-update
  otomatis oleh `StockMovementObserver` (replay movement — selalu konsisten).
- **Otorisasi**: 3 role berjenjang via Filament Shield. `adjustment` hanya
  manager/admin — ditegakkan di **Policy + Observer** (berlaku walau lewat Model
  langsung, bukan hanya UI).
- **Masking harga modal**: field & kolom `harga_modal` **hidden total** untuk
  staff (bukan `****`).
- **Audit trail**: setiap pergerakan stok tercatat di activity log dengan causer;
  `adjustment` punya deskripsi khusus. Tab **Riwayat Audit** per produk.
- **Dashboard** (1 StatsOverview + 7 ChartWidget native Chart.js):
  1. KPI (StatsOverview) — nilai inventory hanya manager/admin.
  2. Stok per kategori (bar).
  3. Nilai inventory per kategori (doughnut, manager/admin).
  4. Tren stok masuk vs keluar 30 hari (line, live polling 15s).
  5. Stok kritis & menipis (bar warna kondisional).
  6. Dead stock (bar).
  7. Top 10 fast-moving (horizontal bar, manager/admin).
  8. Analisis ABC / Pareto (bar + line, manager/admin).

## Keamanan (ringkas)

- Mass assignment ditutup: `current_stock` di luar `$fillable` (hanya via
  Observer).
- Rate limiting login bawaan Filament aktif (5 percobaan).
- CSRF tetap aktif (middleware `PreventRequestForgery`), password di-hash.
- Detail keputusan di [`DECISIONS.md`](DECISIONS.md).

## Coding Standards

- **Laravel Boost** memasang guidelines ke `CLAUDE.md` dan skills ke
  `.claude/skills/` (termasuk `laravel-best-practices`).
- **Guideline Spatie** tersedia di
  [`docs/laravel-php-guidelines.md`](docs/laravel-php-guidelines.md) + prinsip
  keamanan di `CLAUDE.md`.

---

## Peta Branch

16 fitur (`mX.Y`), tiap fitur sepasang branch `starter/` (soal) & `solution/`
(jawaban). `main` = hasil merge `solution/m5.4-capstone`.

| Modul | Fitur | Slug branch |
|-------|-------|-------------|
| 0 | m0.1 Backend Bootstrap | `*/m0.1-backend-bootstrap` |
| 0 | m0.2 Brand & Theme | `*/m0.2-brand-theme` |
| 1 | m1.1 Product & Category | `*/m1.1-product-category` |
| 1 | m1.2 Supplier & Relasi | `*/m1.2-supplier` |
| 2 | m2.1 Auth & Login | `*/m2.1-auth-login` |
| 2 | m2.2 Role & Shield | `*/m2.2-shield-roles` |
| 3 | m3.1 Stock Movement | `*/m3.1-stock-movement` |
| 3 | m3.2 Dashboard Dasar | `*/m3.2-dashboard-basic` |
| 4 | m4.1 Masking Harga Modal | `*/m4.1-cost-masking` |
| 4 | m4.2 Audit Trail | `*/m4.2-audit-trail` |
| 4 | m4.3 Tren Stok (Live) | `*/m4.3-trend-chart` |
| 4 | m4.4 Kritis & Dead Stock | `*/m4.4-critical-deadstock` |
| 5 | m5.1 Hardening | `*/m5.1-hardening` |
| 5 | m5.2 Testing Lengkap | `*/m5.2-testing` |
| 5 | m5.3 Dashboard Lanjutan | `*/m5.3-advanced-dashboard` |
| 5 | m5.4 Capstone | `*/m5.4-capstone` |

---

## Next Steps: Integrasi Laravel AI SDK & Rekomendasi

Aplikasi ini disiapkan untuk dikembangkan dengan **Laravel AI SDK**
(`laravel/ai`) tanpa refactor besar. Seluruh logika agregasi sudah terpusat dan
bersifat **read-only** di `app/Services/InventoryAnalyticsService.php` — method
seperti `stockByCategory()`, `criticalStockProducts()`, `deadStockProducts()`,
`movementTrend()`, `topMovingProducts()`, dan `abcAnalysis()` bisa langsung
dibungkus menjadi **Tool read-only** untuk sebuah AI agent (mis. "kategori mana
yang nilai inventory-nya paling besar?", "produk apa yang mendekati stok
kritis?") tanpa menduplikasi query.

Untuk aksi yang **mengubah data** — misalnya tool yang otomatis membuat
rekomendasi/purchase order atau menyesuaikan stok — sebaiknya memakai fitur
**human-in-the-loop approval** dari `laravel/ai` (`Approvable` +
`InteractsWithApprovals`). Pola ini konsisten dengan pembatasan peran yang sudah
dibangun di aplikasi ini: aksi sensitif seperti `adjustment` stok hanya boleh
dilakukan manager/admin. Dengan menuntut persetujuan manusia berwenang sebelum
tool AI mengeksekusi perubahan, kebijakan otorisasi yang sama tetap ditegakkan
walau pemicunya adalah agen AI, bukan klik manual di UI.
