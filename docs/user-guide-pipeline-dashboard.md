# Panduan: Dasbor Pipeline

## Gambaran Umum

**Dasbor Pipeline** (`/pipeline-dashboard`) memantau sebaran status seluruh entitas yang dikelola
pipeline. Halaman ini membantu administrator dan manajer operasional melihat berapa banyak entitas
di setiap status, status mana yang menumpuk, dan entitas mana yang terlalu lama berada di status
perantara.

Dasbor hanya membaca pipeline yang berstatus aktif. Bila belum ada pipeline aktif, halaman
menampilkan keadaan kosong.

## Akses & Izin

Halaman memerlukan permission **`pipeline_dashboard`**. Permission ini hanya untuk melihat data;
tidak ada tombol yang mengubah pipeline atau entitas dari dasbor. Menu berada di
**Admin > Dasbor Pipeline**.

## Menu & Navigasi

- **Admin > Dasbor Pipeline**
- Halaman berisi panel filter, kartu ringkasan per status, grafik distribusi status, dan tabel
  entitas stale.

## Filter

| Filter | Pilihan | Keterangan |
|---|---|---|
| **Branch** | Daftar cabang atau **All Branches** | Membatasi data ke satu cabang. |
| **Select Pipeline** | **All Active Pipelines** atau nama pipeline | Membatasi data ke satu pipeline. |
| **Stale Threshold** | 3, 7, 14, atau 30 hari | Batas umur di status perantara sebelum dianggap stale. |

Bila hanya ada satu pipeline aktif, pipeline tersebut dipilih otomatis. Perubahan filter langsung
memuat ulang data.

## Kartu ringkasan status

Setiap status pada pipeline aktif ditampilkan sebagai kartu berisi nama status dan jumlah entitas.
Warna garis atas kartu mengikuti warna status yang dikonfigurasi pada pipeline. Bila beberapa
pipeline dipilih, kartu dikelompokkan per pipeline agar status dengan nama sama tidak tercampur.

Kartu menampilkan seluruh status pada pipeline yang memiliki entitas. Pipeline yang seluruh
statusnya kosong tidak ditampilkan. Bila semua pipeline kosong, halaman menampilkan
**No entities in any state yet** dan penjelasan bahwa distribusi muncul setelah ada entitas masuk
ke pipeline.

## Grafik distribusi status

Grafik donat menampilkan proporsi entitas per status. Angka di tengah adalah total entitas yang
terhitung. Legenda di bawah grafik menampilkan nama status, warna, dan persentase. Hanya status
dengan jumlah lebih dari nol yang ditampilkan pada legenda.

## Tabel entitas stale

Tabel **Stale Entities** menampilkan entitas yang berada di status perantara lebih lama dari
**Stale Threshold**. Kolom yang tampil:

| Kolom | Keterangan |
|---|---|
| **Type** | Jenis entitas, misalnya Asset. |
| **Entity** | Nama atau kode entitas. |
| **Current State** | Status saat ini, dengan warna status. |
| **Last Transition** | Waktu transisi terakhir dan nama pelaku. |
| **Days Stale** | Jumlah hari sejak transisi terakhir. |

Daftar diurutkan dari transisi paling lama dan dibatasi 50 baris. Bila tidak ada entitas stale,
tabel menampilkan **No stale entities found** dan **Everything is moving smoothly through the
pipeline.**

## Aturan dan validasi penting

- **Hanya pipeline aktif** yang dihitung. Pipeline nonaktif tidak muncul pada filter maupun kartu.
- **Status stale hanya dihitung pada status perantara**. Status awal dan status akhir tidak
  dianggap stale.
- **Batas stale** dihitung dari waktu transisi terakhir, bukan tanggal pembuatan entitas.
- **Data mengikuti cabang pengguna**. Pengguna dengan akses semua cabang dapat memilih cabang lain;
  pengguna dengan satu cabang hanya melihat cabangnya.
- **Angka pada kartu dan grafik** berasal dari status pipeline saat ini, bukan riwayat transisi.
  Untuk riwayat, gunakan **Jejak Audit Pipeline**.

## Tanya jawab singkat

**Mengapa dasbor kosong?**
Belum ada pipeline aktif, belum ada entitas yang masuk pipeline, atau filter yang dipilih tidak
memiliki data. Coba pilih **All Active Pipelines** dan **All Branches**.

**Mengapa suatu entitas tidak muncul di tabel stale?**
Entitas tersebut mungkin berada di status awal atau akhir, atau belum melewati batas stale yang
dipilih. Naikkan atau turunkan **Stale Threshold** untuk memeriksa.

**Apakah dasbor bisa mengubah status entitas?**
Tidak. Perubahan status dilakukan dari halaman detail entitas melalui tombol transisi pipeline.

**Di mana riwayat perpindahan status dapat dilihat?**
Buka **Admin > Jejak Audit Pipeline**. Panduan lengkapnya ada di
[Jejak Audit Pipeline](./user-guide-pipeline-audit-trail.md).

**Apakah angka dasbor real-time?**
Data dimuat saat halaman dibuka dan saat filter berubah. Muat ulang halaman untuk mengambil data
terbaru setelah ada transisi.
