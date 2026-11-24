# Catatan Upgrade: Kegagalan Jurnal Membatalkan Dokumen

Dokumen ini untuk administrator yang memasang pembaruan posting jurnal (Refs #416).

## Ringkasan Perubahan

Sebelumnya, pada modul berikut kegagalan posting jurnal hanya dicatat sebagai peringatan (`Log::warning`), lalu dokumen tetap tersimpan dan stok tetap bergerak. Sekarang dokumen **ditolak**: transaksi dibatalkan dan API mengembalikan `422` dengan pesan yang dapat diterjemahkan.

| Modul | Aksi yang terdampak |
|-------|---------------------|
| Retur supplier (`supplier-returns`) | create, update |
| Penyesuaian stok (`stock-adjustments`) | create, update. Movement stok kini diposting dalam transaksi yang sama dengan jurnal |
| Goods receipt (`goods-receipts`) | update (termasuk konfirmasi) |

Akibatnya, dokumen, stok, dan status tidak berubah bila jurnal gagal. Tidak ada lagi selisih diam-diam antara stok dan buku besar.

## Syarat Sebelum Dokumen Dikonfirmasi

Dokumen di atas hanya bisa diposting bila:

1. **COA lengkap.** Semua akun yang dipakai posting (persediaan, hutang, selisih stok, dan akun pendukung lain) harus ada di versi COA yang berlaku untuk tanggal dokumen. COA ambigu juga menolak dokumen.
2. **Tahun fiskal tersedia dan terbuka.** Harus ada tahun fiskal yang mencakup tanggal dokumen dan tidak berstatus tertutup atau terkunci.
3. **Status PO sesuai (GR).** GR hanya bisa dikonfirmasi bila purchase order sudah `confirmed` atau `partially_received`.

Pastikan poin 1 sampai 3 dipenuhi sebelum pengguna mengonfirmasi dokumen. Jika tidak, dokumen akan ditolak dengan 422.

## Pesan Kesalahan Baru

| Kunci i18n | Kapan muncul |
|-----------|--------------|
| `journal_entry.fiscal_year_missing` | Tidak ada tahun fiskal untuk tanggal dokumen |
| `journal_entry.fiscal_year_closed` | Tahun fiskal sudah ditutup atau dikunci |
| `accounting_posting.line_account_outside_coa` | Akun baris jurnal bukan bagian dari versi COA |
| `goods_receipt.po_not_approved` | PO belum dikonfirmasi atau diterima sebagian |

## Langkah Upgrade

1. Pull branch atau rilis terbaru.
2. Jalankan `php artisan migrate --force` bila ada migrasi baru pada rilis ini.
3. Periksa COA aktif: pastikan semua akun posting ada untuk tanggal yang akan dipakai.
4. Periksa tahun fiskal: pastikan tahun untuk periode berjalan ada dan terbuka.
5. Uji satu retur, satu penyesuaian stok, dan satu GR di lingkungan staging sebelum dipakai di produksi.

## Tidak Ada Migrasi Data

Perubahan ini tidak mengubah data yang sudah ada. Dokumen lama yang sudah tersimpan tetap apa adanya. Dokumen yang gagal diposting sebelum rilis ini mungkin tidak memiliki jurnal. Periksa dengan laporan rekonsiliasi stok dan jurnal sebelum menutup periode.

## Verifikasi

- Buat retur, penyesuaian stok, atau GR dengan COA yang tidak lengkap: respons harus `422`, dan tidak ada dokumen, movement stok, atau perubahan status yang tersimpan.
- Buat dokumen dengan COA lengkap dan periode terbuka: dokumen tersimpan, jurnal terbentuk, stok bergerak.
