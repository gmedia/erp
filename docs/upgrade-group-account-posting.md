# Upgrade: Posting jurnal ke akun kelompok (Issue #437)

## Perubahan perilaku

- Akun **kelompok** adalah akun yang punya anak. Akun kelompok hanya agregasi laporan.
- Baris jurnal ke akun kelompok **hanya diterima bila memperkecil saldo posted** akun itu:
  - tanda nilai baris berlawanan dengan saldo posted (saldo debit dikurangi kredit), dan
  - nilai mutlak total baris per akun tidak melebihi nilai mutlak saldo (tidak melewati nol).
- Aturan ini berlaku untuk draft maupun posted, di semua jalur jurnal
  (`CreateJournalEntryAction`, `PostJournalAction`). Penolakan: **422** pada field `lines`
  dengan pesan `accounting_posting.line_group_account_not_reducing`.
- Pembalik (reversal) dari baris jurnal asli selalu diterima. Ini diatur di server, bukan dari payload.
- Picker akun di form jurnal manual dan jurnal berulang memakai `/api/accounts?postable=1`:
  akun daun, atau akun kelompok yang saldo posted-nya tidak nol. Akun kelompok bersaldo nol
  tidak ditawarkan.
- Filter `postable=1` ada di server (`IndexAccountsAction`); `has_children=0` tetap berfungsi seperti
  sebelumnya untuk pemakai lain.

## Dampak pada instalasi lama

Instalasi yang sudah punya jurnal ke akun kelompok **tidak diubah otomatis**. Baris lama tetap
ada. Jurnal draft yang menunjuk akun kelompok tidak bisa diposting kecuali memperkecil saldo.

### Langkah 1: cari baris yang menunjuk akun kelompok

```bash
./vendor/bin/sail artisan tinker --execute='
  $groupIds = \App\Models\Account::whereHas("children")->pluck("id");
  echo \Illuminate\Support\Facades\DB::table("journal_entry_lines")
    ->join("journal_entries", "journal_entries.id", "=", "journal_entry_lines.journal_entry_id")
    ->whereIn("journal_entry_lines.account_id", $groupIds)
    ->select("journal_entries.entry_number", "journal_entries.status", "journal_entry_lines.account_id")
    ->get()->toJson();'
```

### Langkah 2: jurnal draft

Ubah akun baris ke akun daun yang benar lewat layar edit draft, lalu posting.

### Langkah 3: jurnal posted (jangan ubah baris langsung)

1. Batalkan dokumen lama lewat layar dokumen. Sistem membuat jurnal pembalik yang diterima
   walaupun memakai akun kelompok (pembalik dari baris asli). Jurnal asli tetap utuh untuk audit.
2. Jika saldo kelompok masih ada setelah pembatalan, buat **jurnal manual** yang memindahkan
   saldo itu ke akun daun: baris ke akun kelompok berlawanan tanda dengan saldonya, dengan nilai
   tidak melebihi saldo; sisi lawan ke akun daun.
3. Jurnal manual seperti itu hanya diterima bila memperkecil saldo. Yang menambah atau membalik tanda ditolak 422.

### Langkah 4: verifikasi

Ulangi langkah 1 sampai hasilnya kosong, dan saldo akun kelompok di Neraca Saldo sudah nol.

Jangan melakukan perubahan massal pada baris posted tanpa persetujuan product owner.

## Verifikasi (test)

- `tests/Feature/JournalEntries/PostingToGroupAccountTest.php`: draft dan posted ke kelompok yang
  menambah saldo ditolak; yang memperkecil diterima; nol tidak lolos; pembalik dokumen lama diterima;
  posting ulang draft diperiksa ulang.
- `tests/Feature/JournalEntries/AccountPostablePickerTest.php`: `postable=1` menampilkan akun daun dan
  kelompok bersaldo, menyembunyikan kelompok bersaldo nol.
- `tests/Feature/Integrity/DemoDataIntegrityTest.php` invarian `W7-06`: data demo tidak memposting
  ke akun kelompok.
