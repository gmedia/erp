# Upgrade: Aging dashboard as_of & diskon terjurnal (Issue #437, langkah 2b)

## Perubahan perilaku

- **Aging dashboard** (`GET /api/aging-dashboard`) kini memakai basis historis yang sama dengan
  laporan AR/AP (`AgingReportBoundaries`): dokumen bertanggal `<= as_of`, sisa dihitung dari
  pelunasan dan credit note bertanggal `<= as_of`, dokumen dengan sisa `> 0` saja. Kartu total,
  bucket, dan Top Overdue sekarang sama dengan laporan untuk tanggal yang sama (termasuk ekspor,
  karena ekspor memakai action laporan yang sama).
- **Diskon pelunasan mengurangi sisa hanya bila terjurnal.** Diskon alokasi (AR `discount_given`,
  AP `discount_taken`) ikut dihitung sebagai pelunasan hanya bila jurnal receipt/payment-nya
  memuat baris akun diskon (AR `41900`, AP `51900`).

## Dampak pada data lama (penting)

Pembayaran yang terposting **sebelum #437** dan punya diskon tersimpan, tetapi tidak dijurnal,
**tidak** mengurangi sisa. Sisa dokumen tetap seperti sebelumnya, sehingga refresh dokumen
tidak tiba-tiba menurunkan saldo piutang/hutang.

Konsekuensinya: diskon lama tersebut tidak diakui sebagai pelunasan di laporan sampai ada
keputusan terpisah (mis. backfill jurnal diskon). Jangan menjalankan backfill otomatis tanpa
persetujuan product owner.

## Verifikasi

- `tests/Feature/AgingDashboard/AgingDashboardAsOfParityTest.php` (baru): dashboard = laporan
  untuk as_of yang sama (AR, AP), invoice setelah as_of diabaikan, diskon tanpa jurnal tidak
  mengurangi sisa.
- `tests/Feature/Reports/AgingAsOfDateTest.php`: test diskon AP kini menjurnal diskon 10000
  (akun `51900`) agar tetap menguji diskon terjurnal.
- Test `AgingDashboardControllerTest` yang bergantung pada tanggal acak kini memakai
  `invoice_date`/`bill_date` tetap sebelum as_of.

## Keterbatasan (tidak berubah di langkah ini)

- Kolom `amount_due` tersimpan di dokumen tidak lagi dipakai dashboard maupun laporan aging;
  nilai historis dihitung dari alokasi bertanggal. Kolom tersimpan tetap dipakai oleh layar
  lain di luar lingkup langkah ini.
- Tanggal pembatalan dokumen belum tersedia di skema (lihat catatan 2a di issue #437).
