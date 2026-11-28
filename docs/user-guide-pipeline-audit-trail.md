# Panduan: Jejak Audit Pipeline

## Gambaran Umum

**Jejak Audit Pipeline** (`/pipeline-audit-trail`) menampilkan riwayat perpindahan status pada
entitas yang dikelola pipeline. Setiap baris mencatat status asal, status tujuan, transisi yang
dipakai, pelaku, waktu, komentar, dan metadata tambahan. Halaman ini dipakai untuk menelusuri siapa
memindahkan entitas, kapan, dan dengan alasan apa.

Berbeda dari **Dasbor Pipeline** yang menampilkan kondisi saat ini, halaman ini menyimpan jejak
historis setiap transisi.

## Akses & Izin

| Kebutuhan | Permission |
|---|---|
| Melihat jejak audit | `pipeline_audit_trail` |
| Mengekspor jejak audit | `pipeline_audit_trail` |

Permission yang sama dipakai untuk melihat dan mengekspor. Menu berada di
**Admin > Jejak Audit Pipeline**.

## Menu & Navigasi

- **Admin > Jejak Audit Pipeline**
- Halaman berisi toolbar pencarian, panel filter, tabel jejak audit, tombol ekspor, dan modal
  detail.

## Tabel

Kolom yang tampil:

| Kolom | Keterangan |
|---|---|
| **Date** | Tanggal dan waktu transisi. |
| **Pipeline** | Nama pipeline yang menaungi entitas. |
| **Entity Type** | Jenis entitas, misalnya Asset. |
| **Entity ID** | Nomor atau ID entitas. |
| **From State** | Status asal. Baris tanpa status asal ditandai **Initial**. |
| **To State** | Status tujuan. |
| **Transition** | Nama transisi yang dijalankan. |
| **Performed By** | Pengguna yang menjalankan transisi, atau **System**. |
| **Comment** | Komentar transisi. Kolom ini tersembunyi secara default. |

Kolom **Comment** dan **Transition** dapat ditampilkan melalui pengaturan visibilitas kolom pada
toolbar tabel. Klik aksi lihat pada baris untuk membuka detail.

## Filter

- **Search** untuk ID entitas, nama pelaku, komentar, atau nama transisi.
- **Entity Type** untuk jenis entitas, misalnya `Asset` atau `Employee`.
- **Pipeline** untuk memilih pipeline tertentu.
- **Performed By** untuk memilih pengguna pelaku.
- Rentang tanggal untuk membatasi waktu transisi.

Filter dapat dikombinasikan. Tekan tombol terapkan pada panel filter untuk menjalankan filter dan
tombol reset untuk mengembalikan ke semua data.

## Detail transisi

Modal **Audit Trail Detail** menampilkan:

- Status asal dan status tujuan beserta warna status.
- Nama transisi, atau **Direct Change** bila tidak ada transisi.
- Jenis dan ID entitas.
- Nama pipeline.
- Pelaku, tanggal dan waktu, alamat IP, dan user agent.
- Komentar transisi.
- Metadata tambahan dalam bentuk snapshot, bila tersedia.

Gunakan detail ini untuk memastikan suatu perubahan status berasal dari transisi yang sah dan
dilakukan oleh pengguna yang berwenang.

## Ekspor

Tombol **Ekspor** mengunduh data sesuai filter dalam format Excel atau CSV. Kolom yang diekspor
mengikuti kolom jejak audit, termasuk komentar dan metadata. Berkas ekspor dapat memuat data
pribadi berupa nama pelaku dan alamat IP, jadi simpan di tempat aman dan bagikan seperlunya.

## Aturan dan validasi penting

- **Jejak audit bersifat baca-saja**. Tidak ada tombol tambah, ubah, atau hapus.
- **Setiap transisi** yang berhasil dicatat, termasuk transisi yang mengubah status awal.
- **Status awal** ditampilkan sebagai **Initial** karena belum memiliki status asal.
- **Pelaku sistem** ditampilkan sebagai **System** bila transisi dijalankan otomatis.
- **Filter tanggal** memakai tanggal transisi, bukan tanggal pembuatan entitas.

## Tanya jawab singkat

**Mengapa jejak audit suatu entitas kosong?**
Entitas mungkin belum pernah masuk pipeline, atau pipeline untuk jenis entitas tersebut belum
pernah dijalankan. Periksa juga filter yang sedang aktif.

**Apakah komentar wajib pada setiap transisi?**
Tidak selalu. Kewajiban komentar ditentukan pada konfigurasi transisi di pipeline. Bila diwajibkan,
transisi tidak dapat dijalankan tanpa komentar.

**Apa arti Direct Change?**
Perubahan status tercatat tanpa transisi bernama, misalnya perubahan yang dilakukan langsung oleh
sistem atau proses pemeliharaan data.

**Bisakah jejak audit dihapus?**
Tidak dari halaman ini. Jejak audit dipertahankan untuk keperluan audit sesuai kebijakan retensi
perusahaan.

**Di mana saya bisa melihat status saat ini?**
Buka **Admin > Dasbor Pipeline** atau halaman detail entitas. Panduan dasbor ada di
[Dasbor Pipeline](./user-guide-pipeline-dashboard.md).
