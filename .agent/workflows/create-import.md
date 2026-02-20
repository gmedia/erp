---
description: Menambahkan fitur Import Excel pada modul CRUD existing
---

# /create-import

Gunakan workflow ini untuk menginstruksikan agent agar membuat fitur **Import** pada sebuah modul CRUD existing (contoh: `Employees`, `Assets`).

## Langkah-langkah Penggunaan

Kirim prompt kepada agent dengan format berikut:

```text
/create-import

Tambahkan fitur Import Excel pada modul CRUD `{ModulNames}`.

Spesifikasi:
- Format file: Excel (.xlsx, .xls) dan CSV (.csv)
- Kolom import: [isi daftar kolom name, email, dll]
- Kolom Unik (Upsert Key): [isi kolom penentu upsert misal: email, code]
- Keterangan FK (Foreign Keys):
  - [field_id] lookup ke [tabel] berdasarkan kolom [name]
  - Jika FK name tidak ditemukan → row gagal
- Validasi per-row: [referensi ke aturan unik/format, misal: ikuti validasi StoreRequest]

Instruksi:
1. Gunakan skill `feature-import` → baca SKILL.md
2. Pelajari pattern import dari modul `Employees` sebagai referensi struktur.
3. Output yang diharapkan:
   - Backend: Importer class (`app/Imports/{Module}Import.php`), Action class, Form Request, Controller methods, Route.
   - Frontend: Komponen Dialog Upload file & Summary tampilan, tombol navigasi di index.
   - Testing Pest: Test feature upload (`tests/Feature/{Modules}/{Module}ImportTest.php`). Test validasi dan test FK resolution.
4. Standar testing:
   - Group annotation: `->group('{modul-names}')` di SEMUA test file (wajib).
5. Verifikasi:
   - `./vendor/bin/sail test --group {modul-names}`
```

## Referensi

Untuk melihat aturan arsitektur secara detail, agent akan membaca panduannya di `.agent/skills/feature-import/SKILL.md`.
