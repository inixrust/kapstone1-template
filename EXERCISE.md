# EXERCISE m5.5 — Landing Page ber-brand Inixindo

Halaman `/` masih memakai template bawaan Laravel. Ganti dengan **landing page**
publik yang menarik, konsisten dengan brand Inixindo Bandung, dan mengarahkan
pengunjung masuk ke panel admin.

## Tujuan Pembelajaran
- Membuat halaman Blade publik yang berdiri sendiri (di luar panel Filament).
- Menerapkan brand: memakai aset logo dan palet warna yang sama dengan panel.
- Progressive enhancement: halaman tetap utuh tanpa JS; animasi hanya tambahan.
- Menghindari ketergantungan build (Vite/npm) agar andal di Laravel Octane.

## Konteks yang Sudah Tersedia
- Aset logo: `public/images/brand/logo.png` (panggil via `asset(...)`).
- Palet brand (lihat `app/Providers/Filament/AdminPanelProvider.php`):
  navy `#143564`, merah `#EA2339`, sky blue `#5CAAD5`, hijau `#1F9254`.
- Panel admin di route `/admin` (login Filament).
- Route `/` sudah mengembalikan `view('welcome')`.

## Yang Perlu Dibangun
Ubah `resources/views/welcome.blade.php` menjadi landing page dengan bagian:
1. **Header** sticky: logo + nama brand + tombol "Masuk Panel" (→ `/admin`).
2. **Hero**: judul kuat, sub-judul, dua CTA (utama ke `/admin`), dan sebuah
   visual ringkasan (boleh mock KPI/grafik statis).
3. **Fitur**: minimal 6 kartu fitur inti (master data, pergerakan stok,
   masking harga modal, audit trail, dashboard, analisis ABC).
4. **Peran & Akses**: 3 kartu untuk staff / manager / admin beserta ringkasan
   hak akses masing-masing.
5. **Teknologi**: deretan pill stack (Laravel, Filament, MySQL, Redis, dst.).
6. **CTA penutup** + **footer** ber-brand.

## Batasan Teknis
- **Self-contained**: seluruh CSS/JS inline di dalam view; ikon pakai SVG inline.
  Tidak boleh menambah package atau bergantung pada `npm run build`.
- Bungkus blok `<style>` dalam `@verbatim ... @endverbatim` agar `@media`,
  `@keyframes`, dsb. tidak ditafsirkan Blade. Gunakan `{{ asset(...) }}` untuk
  aset (di luar blok verbatim).
- **Responsif** (mobile → desktop) dan mendukung **dark/light**
  (`prefers-color-scheme`).
- Efek reveal-on-scroll (mis. `IntersectionObserver`) bersifat *progressive
  enhancement*: state tersembunyi hanya aktif saat JS menandai `<html>`, sehingga
  tanpa JS seluruh konten tetap tampil.

## Acceptance Criteria
- `GET /` → **200** dan menampilkan konten landing (bukan template Laravel).
- Logo `/images/brand/logo.png` termuat (200).
- Semua tombol "Masuk Panel"/CTA mengarah ke `url('/admin')`.
- Menonaktifkan JavaScript → seluruh bagian tetap terlihat (uji cepat di
  DevTools).
- Tinjauan manual: rapi di lebar 375px dan 1280px, serta di mode gelap & terang.

## Tips
- `clamp()` untuk ukuran font hero yang adaptif.
- Warnai ikon/aksen dari palet brand; jaga kontras teks di atas latar navy.
- Cache view saat menguji Octane: `php artisan view:clear` lalu
  `php artisan octane:reload` setelah mengubah Blade.
