---
stepsCompleted: ['step-01-preflight', 'step-02-select-framework', 'step-03-scaffold-framework', 'step-04-docs-and-scripts', 'step-05-validate-and-summary']
lastStep: 'step-05-validate-and-summary'
lastSaved: '2026-11-08'
---

# Setup Framework Test — inventory

## Step 1 — Preflight

- **Stack terdeteksi**: `fullstack` secara fungsional — Laravel 13 + Filament 5 (PHP, `composer.json`) dengan bundler Vite 8 (`package.json`, `vite.config.js`). Algoritma auto-deteksi TEA tidak mengenali `composer.json`, sehingga hanya sisi `frontend` yang di-scaffold; sisi backend sudah dicakup Pest 4 (116 test) dan dipertahankan sesuai permintaan.
- **Framework E2E sebelumnya**: tidak ada (`playwright.config.*`/`cypress.config.*` tidak ditemukan).
- **Konteks**: `AGENTS.md`, `DECISIONS.md`, `PROGRESS.md`; auth = form login sesi Filament (`/admin/login`), tanpa REST API. Akun demo dari `UserSeeder`/`RoleSeeder`.
- **Lingkungan**: app Sail di `http://localhost:8000` (`artisan serve`, satu proses), Node 24 di host.

## Step 2 — Pemilihan framework

- **Playwright** (`@playwright/test` 1.65) untuk E2E browser: diminta eksplisit, multi-konteks per role memudahkan uji otorisasi, trace/JUnit untuk CI.
- **Pest** tetap untuk feature/unit test PHP — tidak diubah.

## Step 3 — Scaffold

Mode eksekusi: `sequential`.

| Berkas | Isi |
| --- | --- |
| `playwright.config.ts` | timeout 15/30/60 s, expect 10 s, `BASE_URL` fallback, reporter list+HTML+JUnit, trace `retain-on-failure-and-retries`, screenshot/video saat gagal, proyek `setup` → `chromium`, `retries`/`forbidOnly` di CI |
| `tests/e2e/auth.setup.ts` | login per role → `tests/.auth/<role>.json`, memakai ulang sesi valid |
| `tests/e2e/login.spec.ts` | login form, password salah, sesi tiap role |
| `tests/e2e/authorization.spec.ts` | kolom Harga Modal tersembunyi untuk staff / terlihat untuk manager, staff create kategori → 403, tamu → login |
| `tests/e2e/category.spec.ts` | factory + cleanup otomatis, `waitForResponse` Livewire sebelum aksi (network-first) |
| `tests/support/fixtures/index.ts` | `mergeTests(auth, category)` |
| `tests/support/fixtures/auth-fixture.ts` | `pageAs(role)` + penutupan konteks otomatis |
| `tests/support/fixtures/category-fixture.ts` | `createCategory()` via UI manager, hapus saat teardown |
| `tests/support/fixtures/factories/category-factory.ts` | `buildCategory(overrides)` nama unik UUID |
| `tests/support/helpers/accounts.ts` | akun demo, override env |
| `tests/support/page-objects/login-page.ts` | form login + deteksi throttle |
| `.nvmrc` | `24` |
| `.gitignore` | `playwright-report`, `test-results`, `blob-report`, `tests/.auth` |

Pact: dilewati — tidak ada batas consumer-provider (Filament/Livewire memanggil backend sendiri, tanpa API).

## Step 4 — Dokumentasi & skrip

- `tests/README.md`: setup, menjalankan, arsitektur, praktik terbaik, CI, troubleshooting, hook, referensi pengetahuan.
- `package.json`: `test:e2e`, `test:e2e:headed`, `test:e2e:ui`, `test:e2e:debug`, `test:e2e:report`; devDependency `@playwright/test`, `@types/node`.
- Hook TEA: `.claude/hooks/tea-enforce.cjs` (salinan byte-per-byte, sha256 `e94cc2dd…f594d`), `.tea/enforce-config.json` (`testGlobs: tests/**/*.spec.{ts,js}`), `.claude/settings.json` (Pre/Post/Stop). Ketiga spec lolos hook `--pre`.
- `AGENTS.md`: satu baris cara menjalankan E2E.

## Step 5 — Validasi

- `npm run test:e2e`: **12 passed** (3 setup + 9 test), diulang beberapa kali; tidak ada sisa data `E2E Kategori` di database.
- Pest: **116 passed** — tidak terpengaruh.

### Asumsi & penyimpangan dari template (dicatat sesuai instruksi)

1. **playwright-utils tidak dipasang** (`tea_use_playwright_utils: true` di config): sesi non-interaktif dan tidak diminta eksplisit; opsi paling sederhana = Playwright vanilla (cabang "disabled"). Bisa ditambahkan nanti lewat workflow `framework`.
2. **Tanpa `@faker-js/faker`**: factory memakai sufiks UUID (`node:crypto`) agar tidak menambah dependensi; data cukup untuk keunikan.
3. **`.env.example` Laravel tidak diubah**: Playwright tidak memuat `.env` (tanpa dotenv); variabel `BASE_URL`, `E2E_*` didokumentasikan di `tests/README.md` dengan default di kode.
4. **Paralelisme default 1 worker**: server `artisan serve` Sail satu proses membuat browser paralel timeout; `E2E_WORKERS` untuk menaikkan.
5. **Tanpa `data-testid`**: Filament tidak menghasilkannya; selector memakai role/label/id Filament (`[id="form.email"]`) dan `.fi-modal-window` untuk modal.
6. **Cleanup di teardown fixture**, bukan method `cleanup()` terpisah pada factory.
7. **E2E memakai database dev** (app yang berjalan), bukan `testing`; semua data yang dibuat dibersihkan fixture.
8. **Throttle login Filament (5/menit/IP)**: sesi disimpan dan dipakai ulang; spec login gagal dengan pesan jelas bila throttle aktif.
