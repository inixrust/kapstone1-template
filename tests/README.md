# Pengujian

Proyek ini memakai dua lapis test:

| Lapis | Alat | Lokasi | Dijalankan di |
| --- | --- | --- | --- |
| Feature/unit (policy, observer, resource Filament via Livewire) | Pest 4 | `tests/Feature`, `tests/Unit` | container Sail, database MySQL `testing` |
| E2E browser panel admin | Playwright | `tests/e2e`, `tests/support` | host (Node), terhadap aplikasi Sail di `http://localhost:8000` |

Pest tetap lapis utama: aturan bisnis dan matriks otorisasi dibuktikan di sana. Playwright hanya untuk alur yang butuh browser sungguhan (login, render kolom per role, interaksi Livewire).

## Pest

```bash
docker compose exec -T -u sail laravel.test php artisan test --compact
```

## Playwright

### Setup

1. Aplikasi Sail berjalan dan sudah di-seed (akun demo `admin@demo.com`, `manager@demo.com`, `staff@demo.com`, password `password`):
   ```bash
   docker compose up -d
   docker compose exec -T -u sail laravel.test php artisan migrate --seed
   ```
2. Node sesuai `.nvmrc` (24), lalu di host:
   ```bash
   npm install
   npx playwright install chromium
   ```

### Menjalankan

```bash
npm run test:e2e            # headless, semua spec
npm run test:e2e:headed     # browser terlihat
npm run test:e2e:ui         # mode UI Playwright
npm run test:e2e:debug      # inspector, langkah per langkah
npm run test:e2e:report     # buka laporan HTML terakhir
npx playwright test tests/e2e/login.spec.ts   # satu file
```

Variabel lingkungan (opsional):

| Variabel | Default | Fungsi |
| --- | --- | --- |
| `BASE_URL` | `http://localhost:8000` | URL aplikasi yang diuji |
| `E2E_PASSWORD` | `password` | password akun demo |
| `E2E_ADMIN_EMAIL` / `E2E_MANAGER_EMAIL` / `E2E_STAFF_EMAIL` | `<role>@demo.com` | email akun per role |
| `E2E_WORKERS` | `1` | jumlah worker paralel |

### Dua batasan lingkungan yang perlu diketahui

- **Satu worker.** Sail menjalankan `artisan serve` (server PHP bawaan, satu proses), sehingga browser paralel saling menunggu dan login timeout. Naikkan `E2E_WORKERS` hanya bila server mendukung request paralel (mis. di CI dengan PHP-FPM/Octane).
- **Throttle login Filament: 5 percobaan/menit per IP.** Proyek `setup` (`tests/e2e/auth.setup.ts`) menyimpan sesi tiap role di `tests/.auth/` (di-gitignore) dan memakainya ulang selama masih valid, jadi satu run normal hanya mengisi form login 2 kali. Bila spec login gagal dengan pesan "Login Filament ter-throttle", tunggu 1 menit atau jalankan `docker compose exec -T -u sail laravel.test php artisan cache:clear`.

## Arsitektur E2E

```
playwright.config.ts            timeout standar, reporter list+HTML+JUnit, artefak gagal
tests/e2e/
  auth.setup.ts                 login per role → tests/.auth/<role>.json
  login.spec.ts                 login form, password salah, sesi tiap role
  authorization.spec.ts         harga modal tersembunyi untuk staff, 403, redirect tamu
  category.spec.ts              contoh factory + cleanup + tunggu request Livewire
tests/support/
  fixtures/index.ts             mergeTests — SATU-SATUNYA sumber `test`/`expect` untuk spec
  fixtures/auth-fixture.ts      pageAs(role): halaman baru dengan sesi role tersebut
  fixtures/category-fixture.ts  createCategory(): buat via UI sebagai manager, hapus otomatis
  fixtures/factories/category-factory.ts buildCategory(): data unik (UUID) dengan override
  helpers/accounts.ts           akun demo & path storage state
  page-objects/login-page.ts    form login Filament + deteksi throttle
```

- **Fixture**: impor `test` dan `expect` dari `../support/fixtures`, bukan dari `@playwright/test`. Fixture baru dibuat di `tests/support/fixtures/` lalu digabung di `index.ts`.
- **Otorisasi**: pakai `pageAs('staff')` / `pageAs('manager')` untuk membuktikan akses. Seperti di Pest, sesi `admin` memegang semua permission, jadi tidak membuktikan pembatasan permission (admin tidak melewati Policy: Shield `define_via_gate = false`, syarat lain di Policy tetap berlaku).
- **Data**: test berjalan di database dev (bukan `testing`), jadi setiap data yang dibuat harus lewat fixture yang membersihkannya sendiri. Jangan bergantung pada jumlah baris data demo.
- **Stok**: jangan mengubah `current_stock` di luar pergerakan stok; test E2E yang menyentuh stok harus membuat `StockMovement` lewat UI.

## Praktik terbaik

- **Selector**: Filament tidak menghasilkan `data-testid`. Urutan prioritas: `getByRole` dengan nama aksesibel (kolom, tombol, navigasi) → `getByLabel` sesuai label Indonesia di resource → id yang dihasilkan Filament (`[id="form.email"]`) → kelas `fi-*` hanya bila tidak ada alternatif (mis. `.fi-modal-window`, karena modal Filament tidak terekspos sebagai `role=dialog`).
- **Tanpa sleep**: jangan pakai `waitForTimeout`. Tunggu kondisi nyata: `expect(...).toBeVisible()`, `toHaveURL`, atau `waitForResponse` untuk request Livewire (`/livewire…/update`) yang dipasang **sebelum** aksi pemicunya.
- **Isolasi**: setiap test mandiri, data unik per test, tanpa `test.only` (CI memakai `forbidOnly`).
- **Given/When/Then**: tulis struktur test sesuai alurnya; nama test berbahasa Indonesia dan menjelaskan perilaku.

## CI

- Jalankan aplikasi (Sail atau PHP-FPM), `migrate --seed`, lalu `npx playwright install --with-deps chromium` dan `npm run test:e2e`.
- Di CI (`CI=true`): `retries: 2`, `forbidOnly` aktif.
- Unggah `playwright-report/` dan `test-results/` (berisi `junit.xml`, trace, screenshot, video) sebagai artefak saat gagal.

## Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| "Login Filament ter-throttle" | Lebih dari 5 login/menit per IP. Tunggu 1 menit atau `php artisan cache:clear` (lewat Sail, `-u sail`). |
| Login/setup timeout, tombol "Sign in" tertahan loading | Worker paralel ke server `artisan serve` satu proses. Pakai `E2E_WORKERS=1` (default). |
| Semua test gagal di `goto` | Aplikasi belum jalan: `docker compose up -d`, cek `curl -I http://localhost:8000/admin/login`. |
| Error 500 `touch(): Utime failed` | Perintah artisan pernah dijalankan sebagai root; selalu pakai `-u sail`. |
| Sesi role lama tidak valid | Hapus `tests/.auth/*.json`; `auth.setup.ts` akan login ulang. |
| Sisa data `E2E Kategori …` | Run terhenti sebelum teardown fixture; hapus lewat panel (manager/admin). |
| Melihat penyebab kegagalan | `npm run test:e2e:report` atau `npx playwright show-trace test-results/<test>/trace.zip`. |

## Penegakan saat menulis (hook TEA)

`.claude/settings.json` memasang `.claude/hooks/tea-enforce.cjs` (salinan persis dari skill `bmad-testarch-framework`) pada `PreToolUse`, `PostToolUse`, dan `Stop` Claude Code. Hook memblokir penulisan file yang cocok dengan `testGlobs` di `.tea/enforce-config.json` (`tests/**/*.spec.{ts,js}`) bila melanggar aturan mekanis seperti `test.only`, `waitForTimeout`/sleep, file > 1000 baris, atau spec tanpa assertion.

- Tingkat keparahan aturan berasal dari `bmad-testarch-test-review/steps-c/criteria-registry.md`, bukan dari hook.
- Mematikan aturan: tambahkan ID-nya ke `disabledRules` di `.tea/enforce-config.json` dan tulis alasannya di pesan commit.
- Jangan mengedit `tea-enforce.cjs`; `hookSha256` di config akan memperingatkan bila salinannya berubah.

## Referensi pengetahuan

Fragmen TEA yang diterapkan (di `.claude/skills/bmad-testarch-framework/resources/knowledge/`): `playwright-config.md`, `fixture-architecture.md`, `data-factories.md`, `network-first.md`, `test-quality.md`, `selector-resilience.md`.
