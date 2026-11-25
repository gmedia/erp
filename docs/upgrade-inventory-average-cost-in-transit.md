# Catatan Upgrade: Biaya Rata-rata Bergerak dan Persediaan dalam Perjalanan

Dokumen ini untuk administrator dan tim yang mengoperasikan ERP setelah pembaruan metode biaya persediaan dan akun in-transit (Refs #495).

## Ringkasan Perubahan

- Metode biaya: rata-rata tertimbang bergerak per produk per gudang. Average cost disimpan di `stock_movements.average_cost_after`. Laporan valuasi, stock monitor, opname, dan HPP memakai nilai ini, bukan `products.cost`.
- Barang keluar dinilai dengan average cost yang berjalan saat itu.
- Akun baru **Persediaan dalam Perjalanan** (kode 11310) ditambahkan di CoA seed. Setting `inventory_in_transit_account_id` diatur di Admin Settings > Accounting.
- Transfer dijurnal per tahap: dispatch, receive (penuh atau sebagian), selisih kurang, dan batal (jurnal pembalik).
- Stock monitor dan laporan valuasi menampilkan saldo barang dalam perjalanan.
- Command baru `stock:recalculate-average-cost` untuk menghitung ulang kartu stok historis.

## Dampak bagi Pengguna

- Nilai HPP dan nilai persediaan pada data lama bisa berubah, karena sebelumnya memakai `products.cost`.
- Transfer yang dikirim setelah pembaruan membentuk jurnal ke akun Persediaan dalam Perjalanan. Transfer yang sudah berstatus In Transit sebelum pembaruan tidak dijurnal otomatis.

## Langkah Upgrade

1. Deploy kode.
2. Jalankan migration: `./vendor/bin/sail artisan migrate --force`. Migration menambahkan setting `inventory_in_transit_account_id`, lalu mengisinya otomatis dengan akun berkode **11310** bila akun itu aktif di CoA yang aktif. Bila tidak ada, setting dibiarkan kosong. Migration idempoten dan tidak menimpa setting yang sudah terisi.
3. Pastikan setting akun **Persediaan dalam Perjalanan** terisi di Admin Settings > Accounting.
   - **Penting:** bila akun tidak terisi otomatis, **pengiriman transfer DITOLAK (422)** sampai akun diatur. Penolakan ini disengaja, konsisten dengan akun pelepasan aset, dan bukan galat aplikasi. Atur akun sebelum pengguna mulai mengirim transfer.
4. Jalankan dry-run untuk melihat selisih:
   `./vendor/bin/sail artisan stock:recalculate-average-cost --dry-run`
5. Jalankan tanpa dry-run untuk menulis average cost historis:
   `./vendor/bin/sail artisan stock:recalculate-average-cost`
   Command ini idempoten. Aman dijalankan ulang.
6. Tinjau saldo persediaan dan HPP pada periode yang sudah ditutup. Nilai yang berubah perlu dicatat ke tim akuntansi.
7. Informasikan ke pengguna perubahan metode biaya dan transfer di atas.

## Rollback

Rollback kode mengembalikan perhitungan memakai `products.cost`. Kolom `average_cost_after` yang sudah terisi tetap ada dan tidak mengganggu. Jurnal in-transit yang sudah dibuat tetap ada.
