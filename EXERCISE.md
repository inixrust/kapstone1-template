# EXERCISE m5.1 — Hardening

## Tujuan Pembelajaran
- Mengeraskan aplikasi terhadap mass assignment & bypass otorisasi.
- Memverifikasi proteksi bawaan (rate limit login, CSRF).

## Yang Perlu Dibangun / Diverifikasi
1. `$fillable`/`$guarded` benar di semua Model — khususnya `current_stock`
   TIDAK boleh di-mass-assign (hanya lewat StockMovementObserver).
2. Test bypass Policy lewat manipulasi request langsung ke Model (staff tetap
   ditolak untuk adjustment & delete).
3. Verifikasi rate limiting login bawaan Filament aktif; dokumentasikan di
   README. JANGAN nonaktifkan CSRF di form mana pun (default Laravel).

## Acceptance Criteria
- Test: bypass UI tetap ditolak Policy/Model.
- Rate limit login terverifikasi.
