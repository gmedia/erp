# User Guide: Multi-Currency Status

## Status Saat Ini

Sistem ERP saat ini **terkunci pada IDR** untuk semua transaksi keuangan. Multi-currency display dan FX (foreign exchange) conversion belum tersedia.

## Apa yang Berubah

Sebelumnya, kolom `currency` muncul sebagai input bebas pada beberapa form (Purchase Order, Supplier Bill, Customer Invoice, AP Payment, AR Receipt, Asset). Kolom tersebut sudah **disembunyikan** dari UI karena:

1. Backend tidak melakukan konversi FX. Semua amount diperlakukan sebagai IDR di laporan agregasi (Aging Dashboard, Financial Dashboard, Trial Balance, dll).
2. Memasukkan currency selain IDR (misal USD, EUR) dapat menyebabkan kesalahan perhitungan diam-diam pada total laporan.
3. Untuk mencegah salah input, sistem menolak nilai non-IDR di seluruh write path: API JSON, Excel import, dan setting halaman admin.

## Apa yang Tetap Sama

- Semua transaksi yang sudah ada tetap valid dengan currency `IDR`.
- Tampilan ViewModal/detail tetap menampilkan label "Currency: IDR" untuk transparansi.
- Kolom `currency` di Excel export tetap ada untuk audit historis.
- Setting "Display Currency" di halaman Admin Settings hanya menerima IDR; opsi lain dikembalikan dengan validation error 422.

## Apa yang Akan Datang

Dukungan multi-currency penuh (transaksi multi-mata-uang dengan FX conversion otomatis) sedang direncanakan untuk rilis berikutnya. Roadmap:

1. **Wave 2** (saat customer non-IDR pertama tanda tangan):
    - Tabel `currency_rates` (tanggal, base, target, rate)
    - Kolom `exchange_rate` di tabel transaksi (default 1.0 untuk IDR)
    - Konversi otomatis di laporan agregasi via `ConvertsCurrency` trait
    - Setting `app.base_currency` (config) tetap IDR; display currency dapat berbeda

2. **Wave 3** (opsional, kalau butuh):
    - Locale-aware number formatting per user
    - FX gain/loss accounting otomatis
    - Multi-currency journal entries

## Pertanyaan Umum

**Apakah saya bisa memasukkan transaksi dalam USD sekarang?**
Tidak. Sistem akan menolak (HTTP 422) dengan pesan validasi pada field `currency`. Tunggu rilis multi-currency penuh.

**Saya melihat field `currency` di laporan ekspor Excel — apakah itu masih dipakai?**
Ya, untuk audit historis. Semua row akan menampilkan `IDR`.

**Apakah Aging Dashboard sudah aman dari mixed-currency?**
Ya. Mulai rilis ini, Aging Dashboard memvalidasi homogenitas currency sebelum agregasi. Kalau database memiliki dua mata uang berbeda dalam scope yang sama, dashboard akan mengembalikan HTTP 422 dengan detail mata uang yang ditemukan.

---

**Reference**: `config/app.supported_transaction_currencies` (whitelist), `app/Services/Currency/CurrencyGuard.php` (aggregation guard), `app/Http/Requests/Concerns/HasSupportedCurrencyRules.php` (FormRequest validator).
