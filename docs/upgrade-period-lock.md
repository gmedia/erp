# Catatan Upgrade: Penguncian Periode pada Posting Jurnal

Dokumen ini untuk administrator dan tim yang mengoperasikan ERP setelah pembaruan penguncian periode (Refs #423).

## Ringkasan Perubahan

Sebelumnya, jurnal bisa diposting ke periode yang sudah ditutup. Sekarang:

- Jurnal bertanggal di periode yang berstatus `closed` ditolak 422. Ini berlaku untuk semua jalur posting (buat, posting draft, ubah tanggal draft, pembatalan/pembalik dokumen, dan jurnal berulang yang auto-post).
- Penutupan bulanan (`monthly`) mengunci bulan itu. Penutupan tahunan (`annual`) mengunci seluruh tahun fiskal, lewat `fiscal_year_id`.
- Status `reopened` dan `draft` tidak mengunci.
- Jurnal penutup tahunan bertanggal hari terakhir tahun fiskal (sebelumnya `now()`).
- Membuka kembali periode tahunan **membalik** jurnal penutup dengan jurnal pembalik (mekanisme #375). Jurnal penutup asli tidak dihapus lagi.
- Pengecualian guard untuk jurnal penutup hanya berlaku lewat `ClosePeriodAction` dan `ReopenPeriodAction`. Payload jurnal manual (`journal_type: closing`, `source_type`) tidak bisa melewati penguncian.
- Seeder demo yang memposting membuka sementara periode demo lalu mengembalikannya. Periode yang ditutup pengguna tidak disentuh.

## Dampak bagi Pengguna

- Jurnal yang tanggalnya jatuh di periode tertutup harus dipindah ke periode terbuka, atau periode dibuka kembali lebih dulu.
- Dialog tutup periode menjelaskan bahwa jurnal di periode itu tidak bisa diposting lagi.
- Membuka kembali periode tahunan tidak lagi menghapus jurnal penutup. Jurnal pembalik tercatat di buku besar.

## Langkah Upgrade

1. Pastikan tidak ada periode yang sedang ditutup atau dibuka kembali saat deploy.
2. Deploy kode. Tidak ada migration baru untuk fitur ini.
3. Tinjau jurnal yang sudah terposting di periode yang sekarang berstatus `closed` (dibuat sebelum pembaruan). Data lama tidak diubah otomatis.
4. Periode tahunan yang dibuka kembali sebelum pembaruan kehilangan jurnal penutupnya. Jurnal penutup tersebut perlu dibuat ulang atau dikoreksi manual lewat jurnal penyesuaian.
5. Informasikan ke pengguna bahwa posting ke periode tertutup kini ditolak.

## Penguncian Periode pada Perubahan Stok (Refs #434)

Perubahan stok bertanggal di periode yang berstatus `closed` juga ditolak 422, memakai guard yang sama dengan jurnal (`AssertPeriodOpenForPostingAction`):

- Penerimaan barang (GR), retur pembelian, penyesuaian stok, transfer (kirim dan terima), dan stocktake yang bertanggal di bulan atau tahun tertutup tidak bisa dibuat, diubah, atau diposting ulang.
- Pembalik edit dan pembatalan ikut ditolak bila tanggal dokumen asalnya jatuh di periode tertutup. Penolakan terjadi sebelum ada data yang tersimpan.
- Periode `reopened` dan bulan terbuka tetap menerima perubahan stok.

Langkah upgrade tambahan:

1. Tinjau dokumen stok yang sudah terposting di periode yang sekarang `closed`. Data lama tidak diubah otomatis.
2. Bila ada koreksi stok untuk periode tertutup, buka periode itu lebih dulu, lalu koreksi, lalu tutup kembali.
3. Seeder demo stok memakai `ReopensDemoPeriodClosings` (sama seperti seeder jurnal demo), sehingga `db:seed` tetap berjalan ulang.

## Rollback

Rollback kode tidak mengembalikan penguncian. Jurnal pembalik jurnal penutup yang sudah dibuat tetap valid dan tercatat di buku besar.
