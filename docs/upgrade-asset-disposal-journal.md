# Catatan Upgrade: Jurnal Laba/Rugi Pelepasan Aset

Dokumen ini untuk administrator dan tim yang mengoperasikan ERP setelah pembaruan jurnal pelepasan aset (Refs #442).

## Ringkasan Perubahan

Sebelumnya, pelepasan aset (movement `dispose` atau aset `lost`) tidak membuat jurnal. Sekarang:

- Pelepasan tanpa hasil penjualan (termasuk aset hilang): sisa nilai buku (harga perolehan dikurangi akumulasi penyusutan) dicatat sebagai rugi. Harga perolehan dan akumulasi penyusutan dihapus dari buku.
- Pelepasan dengan hasil penjualan: pengguna mengisi nilai dan akun penerimaan (kas, bank, atau piutang). Selisih terhadap nilai buku menjadi laba atau rugi.
- Tanggal jurnal = tanggal pelepasan, tunduk pada penguncian periode (lihat `upgrade-period-lock.md`). Jurnal di periode tertutup ditolak 422.
- Membatalkan pelepasan membalik jurnalnya (`REV-DISP-{kode aset}`).
- Aset yang sudah dilepas tidak bisa disusutkan lagi setelah tanggal pelepasan.
- Laba/rugi pelepasan tidak masuk arus kas operasi. Seluruh hasil penjualan aset masuk arus kas investasi (PSAK 2).

## Dampak bagi Pengguna

- Halaman **Admin Settings > Accounting** berisi dua akun wajib: akun laba dan akun rugi pelepasan aset. Pilih akun daun aktif di COA.
- Posting pelepasan ditolak 422 sampai kedua akun diisi.
- Form pelepasan (movement Dispose) menampilkan kolom **Sale Amount** opsional. Akun penerimaan muncul hanya bila nilai > 0.
- Profil aset menampilkan nilai hasil jual dan tautan **Lihat jurnal pelepasan** untuk pelepasan yang sudah dijurnal.

## Langkah Upgrade

1. Pastikan tidak ada periode yang sedang ditutup atau dibuka kembali saat deploy.
2. Deploy kode dan jalankan migration. Migration menambahkan dua setting akun (`asset_disposal_gain_account_id`, `asset_disposal_loss_account_id`) dengan nilai kosong, dan kolom hasil penjualan pada `asset_movements`.
3. **Wajib:** buka **Admin Settings > Accounting** dan atur akun laba dan akun rugi pelepasan aset sebelum ada pelepasan baru. Tanpa ini, posting pelepasan ditolak.
4. Aset yang sudah berstatus `disposed` atau `lost` **sebelum** pembaruan **tidak dijurnal otomatis**. Data lama tidak diubah. Bila perlu, catat jurnal koreksinya secara manual lewat jurnal penyesuaian pada periode yang sesuai.
5. Informasikan ke pengguna bahwa pelepasan baru sekarang menghasilkan jurnal dan wajib memilih akun penerimaan bila ada hasil penjualan.
