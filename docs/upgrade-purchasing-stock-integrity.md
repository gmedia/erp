# Catatan Upgrade: Integritas Pembelian dan Stok

Dokumen ini untuk administrator dan tim yang mengoperasikan ERP setelah pembaruan integritas GR, retur, dan penyesuaian stok (Refs #436).

## Ringkasan Perubahan

- Field sistem tidak lagi diterima dari payload. Field berikut diabaikan bila dikirim: `approved_by`, `approved_at` (PO, PR, penyesuaian, transfer), `confirmed_by`, `confirmed_at` (GR), `journal_entry_id`, `shipped_at`, `received_at`. Nilainya diisi server saat transisi status.
- `quantity_accepted` + `quantity_rejected` tidak boleh melebihi `quantity_received` pada GR (422).
- Penyesuaian stok keluar tidak boleh membuat saldo stok disetujui menjadi negatif (422).
- Retur ke supplier dibatasi: qty retur per baris GR tidak boleh melebihi qty diterima dikurangi yang sudah diretur (422). Retur setelah bill lunas tetap diizinkan dalam batas tersebut.
- GR yang melebihi qty PO ditolak 422 tanpa toleransi. Pengecekan dihitung per baris PO, untuk GR yang confirmed, dalam lock PO.
- Penyelesaian stocktake membuat satu draft penyesuaian stok (idempoten, hanya baris yang punya selisih). Stok baru berubah setelah draft tersebut disetujui.

## Dokumen yang Terdampak

| Dokumen | Perubahan perilaku |
|---------|--------------------|
| goods-receipts | Field sistem diabaikan; accepted + rejected <= received; total tidak boleh melebihi qty PO |
| purchase-orders, purchase-requests | `approved_*` diabaikan dari payload |
| stock-adjustments | `approved_*` dan field jurnal diabaikan; keluar tidak boleh membuat saldo negatif |
| stock-transfers | `shipped_at` dan `received_at` diisi server saat transisi |
| supplier-returns | Qty retur dibatasi sisa qty diterima |
| inventory-stocktakes | Selesai membuat draft penyesuaian (lihat butir di atas) |

## Dampak bagi Pengguna

- Pengguna tidak lagi bisa mengatur tanggal atau pengesah approval lewat API. Integrasi eksternal yang mengirim field ini tetap berjalan, tetapi nilainya diabaikan.
- Penerimaan yang melebihi qty PO kini ditolak. Pesanan tambahan harus dibuat sebagai PO baru.
- Stocktake yang selesai tidak lagi langsung mengubah stok. Tinjau dan setujui draft penyesuaian yang dibuat.

## Langkah Upgrade

1. Deploy kode. Tidak ada migration baru untuk perubahan ini.
2. Tinjau GR confirmed yang qty diterimanya melebihi qty PO. Data lama tidak diubah otomatis.
3. Tinjau stocktake yang sudah selesai sebelum pembaruan. Draft penyesuaian tidak dibuat otomatis untuk data lama.
4. Informasikan ke pengguna perubahan perilaku di atas.

## Rollback

Rollback kode mengembalikan perilaku lama. Dokumen yang sudah tersimpan tidak berubah, dan draft penyesuaian yang sudah dibuat tetap ada.
