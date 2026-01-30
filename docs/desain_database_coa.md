# Desain Database Chart of Accounts (COA) dengan Versioning

Dokumen ini menjelaskan struktur database untuk modul Chart of Accounts (COA) atau Daftar Akun yang mendukung versioning. Fitur ini memungkinkan perubahan struktur akun setiap tahunnya namun tetap dapat menyajikan laporan keuangan komparatif antar tahun.

## Strategi Versioning
Setiap tahun fiskal (Fiscal Year) atau periode tertentu dapat memiliki versi COA yang berbeda. Saat tahun fiskal baru dibuat, struktur akun dari tahun sebelumnya akan diduplikasi (cloned) ke versi baru. Ini memastikan bahwa perubahan pada nama akun, hierarki, atau penambahan akun baru di tahun berjalan tidak merusak data historis tahun sebelumnya.

## Struktur Tabel

Berikut adalah tabel-tabel yang akan dibuat:

### 1. `fiscal_years` (Tahun Fiskal)
Menyimpan periode akuntansi.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | UUID | Primary Key |
| `name` | String | Nama tahun fiskal (misal: "2025") |
| `start_date` | Date | Tanggal mulai (misal: 2025-01-01) |
| `end_date` | Date | Tanggal selesai (misal: 2025-12-31) |
| `status` | Enum | `open`, `closed`, `locked` |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

### 2. `coa_versions` (Versi COA)
Menyimpan snapshot atau versi dari struktur akun. Biasanya satu tahun fiskal memiliki satu versi aktif.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | UUID | Primary Key |
| `name` | String | Nama versi (misal: "COA 2025 Standard") |
| `fiscal_year_id` | UUID | Foreign Key ke `fiscal_years` (opsional, jika ini template master maka null) |
| `status` | Enum | `draft`, `active`, `archived` |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

### 3. `accounts` (Daftar Akun)
Menyimpan detail akun untuk setiap versi.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | UUID | Primary Key |
| `coa_version_id` | UUID | Foreign Key ke `coa_versions` |
| `parent_id` | UUID | Self-referencing Foreign Key (untuk hierarki akun) |
| `code` | String | Kode Akun (misal: "110-001"). **Kunci utama untuk perbandingan antar versi.** |
| `name` | String | Nama Akun (misal: "Kas Besar") |
| `type` | Enum | `asset`, `liability`, `equity`, `revenue`, `expense` |
| `sub_type` | String | Kategori lebih detail (misal: `current_asset`, `long_term_liability`) |
| `normal_balance` | Enum | `debit`, `credit` |
| `level` | Integer | Level kedalaman hierarki (untuk kemudahan query) |
| `is_active` | Boolean | Status aktif/tidak |
| `description` | Text | Deskripsi akun |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

### 4. `account_mappings` (Pemetaan Akun)
Tabel opsional namun direkomendasikan untuk menangani perubahan kode akun yang drastis antar versi. Digunakan jika kode akun berubah total (misal: "1100" menjadi "1-1000") sehingga tidak bisa dicocokkan otomatis by `code`.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | UUID | Primary Key |
| `source_account_id` | UUID | FK ke `accounts` (Akun di Versi Lama) |
| `target_account_id` | UUID | FK ke `accounts` (Akun di Versi Baru/Tujuan) |
| `type` | Enum | `merge` (gabung), `split` (pecah), `rename` (ganti nama/kode) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

## Logika Pelaporan (Reporting Logic)

### Laporan Tahun Berjalan
Query sederhana ke tabel `accounts` dan `journal_entries` (future implementation) berdasarkan `coa_version_id` yang aktif untuk tahun tersebut.

### Laporan Komparatif (Tahun Ini vs Tahun Lalu)
1.  Sistem mengidentifikasi Versi COA Tahun Ini (Target) dan Versi COA Tahun Lalu (Source).
2.  Sistem akan mencoba mencocokkan akun berdasarkan kolom `code`.
    *   Jika Akun "100-01" ada di kedua versi, nilainya dibandingkan langsung.
3.  Jika ada perubahan struktur yang kompleks, sistem akan melihat ke tabel `account_mappings`.
    *   Jika ada mapping dari Akun A (Lama) ke Akun B (Baru), maka saldo Akun A di tahun lalu akan ditampilkan sebagai saldo pembanding untuk Akun B di tahun ini.
