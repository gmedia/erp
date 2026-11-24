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

## Rollback

Rollback kode tidak mengembalikan penguncian. Jurnal pembalik jurnal penutup yang sudah dibuat tetap valid dan tercatat di buku besar.
