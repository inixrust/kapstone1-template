# State machine status PO

Label UI (Bahasa Indonesia) di kiri, siapa yang boleh memicu transisi di panah.

```mermaid
stateDiagram-v2
    [*] --> draft: manager membuat (CAP-1)
    draft --> diajukan: manager mengajukan, min. 1 item (CAP-1)
    diajukan --> disetujui: admin selain pembuat (CAP-2)
    diajukan --> ditolak: admin selain pembuat, catatan wajib (CAP-2)
    disetujui --> diterima: manager atau staff (CAP-3)
    ditolak --> [*]
    diterima --> [*]
```

| Status | Item/harga bisa diubah | Bisa dihapus | Transisi keluar |
|---|---|---|---|
| draft | ya (manager) | ya (manager) | diajukan |
| diajukan | tidak | tidak | disetujui, ditolak |
| disetujui | tidak | tidak | diterima |
| ditolak | tidak | tidak | — (final) |
| diterima | tidak | tidak | — (final) |

Setiap transisi mencatat activity (causer = pelaku). Transisi yang tidak ada di tabel ditolak di level domain, bukan hanya disembunyikan di UI.
