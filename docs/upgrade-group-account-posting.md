# Upgrade: Posting jurnal ke akun kelompok ditolak (Issue #437, langkah 5)

## Perubahan perilaku

- Akun **kelompok** adalah akun yang punya anak (`parent_id` dari akun lain). Akun kelompok hanya
  agregasi laporan; jurnal tidak boleh memposting ke sana.
- Penolakan berlaku di **semua jalur jurnal**, untuk status draft maupun posted:
  - `CreateJournalEntryAction` (jurnal manual, jurnal berulang, dan semua posting dokumen otomatis),
  - `PostJournalAction` (posting massal draft).
- Respons: **422** pada field `lines` dengan pesan `accounting_posting.line_account_is_group`.
- Form jurnal manual dan jurnal berulang hanya menawarkan akun dengan `has_children=0`
  (`JournalLineFormFields`), sehingga akun kelompok tidak bisa dipilih dari UI.
- Akun kontrol posting dokumen (AR `11200`, AP `21100`, GR/IR `21150`, inter-branch `1999-IBC`)
  adalah akun daun di COA. Data demo (COA dan dokumen) hanya memakai akun daun; invarian
  `W7-06` di `tests/Feature/Integrity/DemoDataIntegrityTest.php` menjaga hal ini.

## Dampak pada instalasi lama (penting)

Instalasi yang sudah punya jurnal ke akun kelompok **tidak diubah otomatis**. Baris lama tetap
ada, tetapi jurnal draft tersebut tidak bisa diposting dan jurnal baru ke akun itu ditolak.

Cara membereskan (dijalankan oleh admin, satu per satu):

1. Cari baris jurnal yang menunjuk akun kelompok:

   ```bash
   ./vendor/bin/sail artisan tinker --execute='
     $ids = \App\Models\Account::whereIn("id", \App\Models\Account::whereNotNull("parent_id")->select("parent_id"))->pluck("code","id");
     echo \Illuminate\Support\Facades\DB::table("journal_entry_lines")->whereIn("account_id", $ids->keys())
       ->join("journal_entries","journal_entries.id","=","journal_entry_lines.journal_entry_id")
       ->select("journal_entries.entry_number","journal_entries.status","journal_entry_lines.account_id")
       ->get()->toJson();'
   ```

2. Untuk jurnal **draft**: ubah akun baris ke akun daun yang benar (lewat layar jurnal, edit draft),
   lalu posting.
3. Untuk jurnal **posted**: jangan ubah baris langsung. Buat jurnal pembalik (reversal) dari layar
   jurnal, lalu buat jurnal baru ke akun daun yang benar. Jurnal asli tetap utuh untuk audit.
4. Jalankan ulang pemeriksaan di atas sampai hasilnya kosong.

Jangan menjalankan perubahan massal ke baris posted tanpa persetujuan product owner.

### Memindahkan saldo akun kelompok (pemetaan COA)

Instalasi lama bisa punya saldo di akun kelompok (mis. `11000`). Saldo itu perlu dipindahkan ke
akun daun lewat **Pemetaan Akun**:

- **Sumber** = akun kelompok (boleh; ini yang dipetakan).
- **Target** = akun daun di COA versi lain (wajib daun; akun kelompok ditolak 422 dengan pesan
  `account_mapping.target_must_be_leaf`).
- Pilih jenis pemetaan (`merge`, `split`, atau `rename`) sesuai perpindahan saldo, lalu simpan.

Pemetaan hanya mengatur perbandingan saldo lintas COA; ia tidak memindahkan baris jurnal. Untuk
saldo posted di akun kelompok, pastikan jurnal pemindahan tetap dibuat ke akun daun (lihat langkah
posted di atas).

## Verifikasi

- `tests/Feature/JournalEntries/PostingToGroupAccountTest.php`: posted ditolak, draft ditolak,
  posting massal ditolak, akun daun tetap bisa dipakai.
- `tests/Feature/Integrity/DemoDataIntegrityTest.php` invarian `W7-06`: data demo tidak memposting
  ke akun kelompok.
