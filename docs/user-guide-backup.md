# Backup & Restore (Kesehatan Sistem)

Panduan ini menjelaskan backup otomatis database dan object storage, cara memeriksa statusnya di halaman **Kesehatan Sistem**, dan langkah restore. Konfigurasi ada di `config/backup.php` dan `routes/console.php`.

## 1. Apa yang dibackup

| Bagian | Cara | Keterangan |
|---|---|---|
| Database | `backup:run` (dump MariaDB) | Dimasukkan ke arsip setiap hari. |
| Object storage (bucket aplikasi) | `backup:stage-object-storage` menyalin ke folder staging lokal | Dibaca lewat Flysystem; lalu ikut diarsipkan sebagai berkas lokal. |

Object storage **tidak** dibaca langsung oleh paket backup. Staging memakai Flysystem (streaming per berkas) dan dilewati bila ukuran serta waktu ubah berkas tidak berubah.

## 2. Jadwal

| Waktu (server) | Perintah | Fungsi |
|---|---|---|
| 01:25 | `backup:stage-object-storage` | Salin object storage ke staging. |
| 01:30 | `backup:run` | Backup DB + staging ke disk `backups`. |
| 01:45 | `backup:stage-object-storage --clear` | Hapus staging. |
| 02:30 | `backup:clean` | Terapkan retensi. |

Jadwal memakai scheduler Laravel: pastikan `schedule:run` berjalan tiap menit (lihat cek Kesehatan **Scheduler**).

## 3. Tujuan (disk)

- Disk `backups` didefinisikan di `config/filesystems.php`, driver `local`, root dari `BACKUP_LOCAL_ROOT` (default `storage/app/backups`).
- Untuk menyimpan di luar server, arahkan ke disk lain (mis. S3 kedua) dengan mengganti driver/root disk `backups`, atau tambahkan disk baru dan ubah `backup.backup.destination.disks`.
- Pastikan tujuan **terpisah** dari bucket sumber, agar kegagalan bucket tidak ikut menghapus backup.

## 4. Retensi

Diatur di `config/backup.php` bagian `cleanup.default_strategy`:

- 7 hari terakhir: semua arsip disimpan (`keep_all_backups_for_days` = 7).
- Hingga 16 hari: satu arsip harian (`keep_daily_backups_for_days` = 16).
- Setelah itu: satu arsip mingguan dan bulanan sesuai `keep_weekly_backups_for_weeks` / `keep_monthly_backups_for_months`.
- `backup:clean` berjalan setiap hari 02:30.

## 5. Enkripsi arsip

- Enkripsi hanya aktif bila `BACKUP_ARCHIVE_PASSWORD` diisi di `.env` server. Password **tidak** ditulis ke repo, PR, atau issue.
- Simpan password di password manager. Tanpa password itu, arsip terenkripsi tidak bisa dibuka.

## 6. Batasan staging

Dikendalikan lewat env (default di `config/backup.php` bagian `staging`):

| Env | Default | Arti |
|---|---|---|
| `BACKUP_STAGING_MAX_FILES` | 20000 | Jumlah berkas maksimum per run. |
| `BACKUP_STAGING_MAX_BYTES` | 5 GiB | Total byte maksimum per run. |

Bila batas tercapai, sisa berkas **tidak** dibackup dan command mencatat peringatan `Batas staging tercapai`. Naikkan batas hanya jika disk host cukup.

## 7. Cek Kesehatan "Backup terakhir"

Halaman **Kesehatan Sistem** menampilkan kartu **Backup terakhir**:

- Gagal bila belum ada arsip, arsip terakhir lebih dari **26 jam**, atau berukuran 0 byte.
- Gagal bila tujuan tidak dapat dijangkau; pesan tidak menampilkan path atau kredensial.
- Kegagalan backup dikirim sebagai notifikasi in-app dan email ke alamat admin (pengaturan admin).

## 8. Restore (langkah demi langkah)

> Lakukan restore di **server uji** atau database sementara dulu. Jangan menimpa database produksi tanpa rencana.

1. **Ambil arsip.** Salin berkas `.zip` terbaru dari disk `backups` (`storage/app/backups/<nama-app>/`).
2. **Buka arsip.** Jika terenkripsi, gunakan `BACKUP_ARCHIVE_PASSWORD` yang tersimpan (unzip dengan password).
3. **Ekstrak dump.** Berkas `db-dumps/mariadb-<koneksi>.sql.gz`:
   ```bash
   gunzip -c db-dumps/mariadb-laravel.sql.gz > /tmp/restore.sql
   ```
4. **Buat database tujuan** (oleh pengguna dengan hak `CREATE`):
   ```bash
   ./vendor/bin/sail exec -T mariadb mariadb -u<admin> -p -e "CREATE DATABASE erp_restore_test"
   ```
5. **Import dump:**
   ```bash
   ./vendor/bin/sail exec -T mariadb mariadb -u<admin> -p erp_restore_test < /tmp/restore.sql
   ```
6. **Verifikasi.** Bandingkan jumlah baris tabel inti (`users`, `branches`, `assets`, `journal_entries`, `approval_requests`) dengan database asli.
7. **Restore object storage.** Salin isi folder `object-storage` dari arsip ke bucket atau disk tujuan sesuai kebutuhan.
8. **Bersihkan.** Hapus database uji dan salinan dump setelah verifikasi.

## 9. Batasan yang diketahui

- Object storage dibaca lewat staging lokal; bucket besar memerlukan batas env dan ruang disk yang cukup.
- Uji restore sudah dijalankan sekali pada salinan DB (MariaDB Sail): dump dari `backup:run --only-db` diimpor ke database sementara lalu jumlah baris 5 tabel inti (`users` 8, `branches` 4, `assets` 15, `journal_entries` 44, `approval_requests` 20) sama dengan database asli. Database sementara sudah dihapus. Restore object storage belum diuji.
- Panduan restore ini belum mencakup restore ke server produksi otomatis.
