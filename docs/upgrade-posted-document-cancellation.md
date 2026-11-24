# Catatan Upgrade: Pembatalan Dokumen Terposting

Dokumen ini untuk administrator dan tim yang mengoperasikan ERP setelah pembaruan pembatalan dokumen terposting (Refs #375).

## Ringkasan Perubahan

Sebelumnya, `DELETE` atau `PUT status: draft` pada dokumen yang sudah terposting menghapus atau mengembalikan dokumen, sementara jurnal (dan stok) tetap ada. Sekarang:

- `DELETE` hanya diizinkan untuk dokumen draft. Selain draft ditolak dengan 422.
- Dokumen terposting tidak bisa dikembalikan ke draft.
- Pembatalan (`status: cancelled`) dokumen terposting membuat jurnal pembalik dari baris jurnal asli. Jurnal asli tidak diubah dan tetap `posted`.
- Tanggal jurnal pembalik adalah tanggal pembatalan. Bila periode fiskal sudah tertutup, pembatalan ditolak dengan 422.
- Pembatalan kedua pada dokumen yang sama ditolak 422 dan tidak membuat pembalik kedua.
- Item dan nilai dokumen terposting terkunci (422).

## Dokumen yang Terdampak

| Dokumen | Pembalik jurnal | Batasan pihak terkait |
|---------|-----------------|------------------------|
| goods-receipts (confirmed) | Ya, membalik jurnal GR/GR-IR | Ditolak bila ada retur atau bill aktif |
| customer-invoices (sent) | Ya | Ditolak bila masih ada credit note applied |
| supplier-bills (confirmed) | Ya | Ditolak bila masih ada alokasi pembayaran confirmed |
| ar-receipts, ap-payments, credit-notes | Sesuai alur masing-masing | Alokasi dilepas dan due dihitung ulang |

## Dampak bagi Pengguna

- Dokumen yang sudah terposting tidak lagi hilang saat dihapus. Untuk membatalkan, gunakan aksi batal pada baris dokumen dan konfirmasi.
- Laporan GL tetap sama dengan subledger setelah pembatalan, karena pembalik tercatat sebagai jurnal baru.
- Data lama tidak diubah. Dokumen yang sudah terlanjur dihapus atau dikembalikan ke draft sebelum pembaruan tidak dipulihkan otomatis; jurnal lama yang masih `posted` tanpa pembalik perlu ditinjau manual.

## Langkah Upgrade

1. Pastikan tidak ada periode fiskal yang sedang ditutup saat deploy.
2. Deploy kode, lalu jalankan `sail artisan migrate --force` bila ada migration baru.
3. Tinjau jurnal `posted` pada dokumen yang berstatus `cancelled` tetapi tanpa jurnal pembalik (data dari sebelum pembaruan). Koreksi dilakukan manual lewat jurnal penyesuaian; sistem tidak membuat pembalik untuk data lama secara otomatis.
4. Informasikan ke pengguna bahwa aksi hapus pada dokumen terposting tidak lagi tersedia.

## Rollback

Rollback kode tidak menghapus jurnal pembalik yang sudah dibuat. Jurnal tersebut tetap valid dan tercatat di buku besar.
