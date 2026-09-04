# EXERCISE m0.2 — Brand & Theme Setup (Inixindo Bandung)

## Tujuan Pembelajaran
- Menerapkan brand & design system pada panel Filament.
- Mengonfigurasi warna, nama brand, dan logo di `AdminPanelProvider`.

## Kondisi Codebase Saat Ini
- Panel Filament `/admin` sudah aktif dengan tema default.
- Brand assets tersedia di `public/images/brand/` (`logo.png`, `footer.png`).

## Yang Perlu Dibangun
Di `app/Providers/Filament/AdminPanelProvider.php`:
- `->colors([...])` dengan palet brand:
  - `primary` = `#143564` (navy)
  - `danger`  = `#EA2339` (merah brand)
  - `info`    = `#5CAAD5` (sky blue)
  - `success` = `#1F9254`
- `->brandName('Inventory Management — Inixindo Bandung')`
- `->brandLogo(asset('images/brand/logo.png'))`
- `->brandLogoHeight('2.5rem')`

## Acceptance Criteria
- Halaman `/admin` menampilkan logo asli Inixindo dan warna primary navy brand
  (bukan warna default Filament amber).
