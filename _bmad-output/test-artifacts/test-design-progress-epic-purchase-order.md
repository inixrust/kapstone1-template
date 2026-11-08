---
runScope: 'epic-level'
runKey: 'epic-purchase-order'
workflowStatus: 'completed'
totalSteps: 5
stepsCompleted: ['step-01-detect-mode', 'step-02-load-context', 'step-03-risk-and-testability', 'step-04-coverage-plan', 'step-05-generate-output']
lastStep: 'step-05-generate-output'
nextStep: ''
lastSaved: '2026-11-08'
inputDocuments:
  - _bmad-output/specs/spec-purchase-order/SPEC.md
  - _bmad-output/specs/spec-purchase-order/stories.yaml
  - _bmad-output/specs/spec-purchase-order/state-machines.md
  - _bmad-output/specs/spec-purchase-order/brownfield.md
  - AGENTS.md
  - tests/README.md
  - tests/Pest.php
  - tests/Feature/AuthorizationMatrixTest.php
  - tests/Feature/CostMaskingTest.php
  - tests/Feature/AuditTrailTest.php
  - tests/Feature/StockMovementTest.php
  - tests/Feature/HardeningTest.php
  - tests/e2e/authorization.spec.ts
  - tests/support/fixtures/category-fixture.ts
  - app/Observers/StockMovementObserver.php
  - app/Policies/StockMovementPolicy.php
  - database/seeders/RoleSeeder.php
  - playwright.config.ts
  - knowledge/risk-governance.md
  - knowledge/probability-impact.md
  - knowledge/test-levels-framework.md
  - knowledge/test-priorities-matrix.md
  - knowledge/nfr-criteria.md
  - knowledge/playwright-cli.md
---

# Progress test-design — epic purchase-order

## Step 1 — Mode

- Mode: Epic-Level (input: `_bmad-output/specs/spec-purchase-order/` — SPEC.md + stories.yaml + state-machines.md + brownfield.md; tidak ada PRD/ADR).
- `epic_num`: `purchase-order` (slug dari id spec `SPEC-purchase-order`; epik tidak bernomor).
- Prasyarat terpenuhi: 3 story dengan kriteria sukses per capability (CAP-1..CAP-3), konteks brownfield tersedia.

## Step 2 — Konteks

- Config: `tea_use_playwright_utils: true` (paket `@seontechnologies/playwright-utils` tidak terpasang — hanya `@playwright/test`), `tea_use_pactjs_utils: true` tetapi tidak ada Pact/kontrak (tanpa REST API) → fragmen kontrak tidak dimuat. `tea_browser_automation: auto`.
- Stack terdeteksi: **fullstack** (Laravel/Pest backend + Playwright `playwright.config.ts`).
- Fragmen dimuat: risk-governance, probability-impact, test-levels-framework, test-priorities-matrix, nfr-criteria (epik memuat syarat security/reliabilitas), playwright-cli.
- Eksplorasi browser: **dilewati** — halaman Purchase Order belum ada (epik belum diimplementasi); analisis berbasis kode + dokumen.
- Cakupan test yang ada (relevan sebagai regresi): `AuthorizationMatrixTest` (dataset role × permission), `CostMaskingTest` (Livewire `assertFormFieldHidden`/`assertTableColumnHidden`), `AuditTrailTest` (Activity causer/log_name), `StockMovementTest` + `HardeningTest` (replay Observer, adjustment di level model), E2E `authorization.spec.ts` (kolom Harga Modal per role), fixture `category-fixture.ts` (buat via UI + cleanup).
- Gap: belum ada model/factory/test PurchaseOrder; tidak ada test yang membuktikan movement tidak bisa diubah/dihapus; tidak ada pola test untuk aturan yang kebal bypass admin selain adjustment di Observer.
- Pola penting: `userWithRole()` untuk bukti akses; `adminUser()` lolos semua Policy (Shield `intercept_gate = before`); penegakan domain mengikuti preseden `StockMovementObserver::creating()` yang melempar `AuthorizationException`.

## Step 3 — Risiko

13 risiko (register lengkap di `test-design-epic-purchase-order.md`). Skor ≥6: R-001 (admin pembuat memutuskan PO sendiri, 6), R-002 (penerimaan non-atomik/ganda, 6), **R-003 (movement hasil PO bisa diedit/dihapus — 9, BLOCK)**, R-004 (harga modal bocor ke staff, 6), R-005 (transisi status ilegal lolos di domain, 6), R-006 (matriks role PO salah, 6), R-012 (residu data E2E di database dev, 6). NFR: security & reliability biner (100%); performa/konkurensi tanpa ambang → UNKNOWN.

## Step 4 — Cakupan

37 skenario: P0 = 19, P1 = 11, P2 = 4, P3 = 3. Level utama Pest Feature (INT: model/domain + Livewire); E2E Playwright hanya alur utama manager → admin → staff dan render masking staff. Estimasi total ~38–65 jam (~1–2 minggu). Gate: P0 100%, P1 ≥95%, R-003 wajib termitigasi.

## Step 5 — Output

- Mode eksekusi: sequential (epic-level = satu artefak).
- Output: `_bmad-output/test-artifacts/test-design-epic-purchase-order.md`.
- Validasi checklist: lulus — risk ID unik, skor P×I benar, setiap risiko punya baris cakupan, estimasi rentang, eksekusi PR/Nightly/Weekly, gate terdefinisi, NFR tanpa ambang ditandai UNKNOWN.
- Asumsi terbuka: A-1..A-6 (entry point domain di model, jenis exception, enum status, kolom `purchase_order_id`, `created_by`, residu E2E).
