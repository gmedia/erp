# Desain Database: Manajemen Aset Perusahaan

Dokumen ini menjelaskan struktur database untuk modul Manajemen Aset (Fixed Assets & Asset Tracking) dalam sistem ERP. Fokus utama: pencatatan master aset, lokasi & penanggung jawab, pergerakan aset, pemeliharaan, stocktake, serta depresiasi yang terhubung ke jurnal akuntansi.

## 1. Gambaran Umum

### Filosofi Desain

Modul aset dirancang untuk mengelola **siklus hidup lengkap** aset perusahaan — dari perolehan hingga disposal — dengan audit trail yang komprehensif. Setiap perubahan lokasi, penanggung jawab, atau status aset tercatat sebagai movement, memungkinkan tracking historis yang akurat. Depresiasi dihitung periodik dan dapat diposting ke jurnal akuntansi.

### Komponen Utama
*   **Master Aset**: Identitas aset, kategori, spesifikasi, nomor seri, nilai perolehan, status, dan atribut finansial.
*   **Lokasi & Penugasan**: Lokasi fisik (hierarki) dan penanggung jawab (employee/department).
*   **Pergerakan & Riwayat**: Mutasi aset antar lokasi/penanggung jawab untuk audit trail.
*   **Pemeliharaan**: Jadwal dan histori maintenance termasuk biaya dan vendor (supplier).
*   **Stocktake**: Proses inventarisasi berkala untuk validasi keberadaan aset.
*   **Depresiasi**: Perhitungan depresiasi periodik yang dapat diposting sebagai jurnal.

### Hubungan dengan Modul Lain

| Modul | Referensi Desain | Hubungan |
| :--- | :--- | :--- |
| **Pipeline** | `10_pipeline_design.md` | Asset lifecycle (draft → active → disposed) dikelola oleh pipeline |
| **Approval** | `11_approval_design.md` | Disposal aset bernilai tinggi memerlukan approval |
| **Chart of Accounts** | `01_chart_of_accounts_design.md` | Depresiasi diposting sebagai jurnal (debit beban, kredit akumulasi) |
| **Purchasing** | `13_purchasing_design.md` | Aset bisa berasal dari Goods Receipt (linking ke PO) |

### Integrasi dengan Master Data yang Sudah Ada
*   **Cabang**: `branches` (untuk lokasi tingkat cabang).
*   **Organisasi**: `departments`, `employees`.
*   **Vendor**: gunakan `suppliers` (bukan `vendors`).
*   **Akuntansi**: `fiscal_years`, `journal_entries`, `journal_entry_lines`, `accounts`.

### Prinsip Desain
1.  **Complete Audit Trail**: Setiap perubahan lokasi, PIC, atau status tercatat sebagai movement.
2.  **Hierarchical Location**: Lokasi aset mendukung hierarki (Cabang → Gedung → Lantai → Ruang).
3.  **Dual Responsibility**: Aset bisa ditugaskan ke departemen (organisasi) dan/atau karyawan (personal).
4.  **Cached Financial Fields**: `accumulated_depreciation` dan `book_value` disimpan sebagai cache untuk query cepat, dihitung dari `asset_depreciation_lines`.
5.  **Accounting Integration**: Depresiasi bisa diposting sebagai jurnal, menghubungkan asset management dengan general ledger.

---

## 2. Diagram Hubungan Entitas (ERD)

```mermaid
erDiagram
    branches ||--o{ asset_locations : has
    asset_locations ||--o{ asset_locations : parent

    asset_categories ||--o{ asset_models : groups
    asset_models ||--o{ assets : defines

    branches ||--o{ assets : owns_in_branch
    asset_locations ||--o{ assets : located_at
    departments ||--o{ assets : responsible_unit
    employees ||--o{ assets : responsible_person
    suppliers ||--o{ assets : purchased_from

    assets ||--o{ asset_movements : moves
    branches ||--o{ asset_movements : from_to_branch
    asset_locations ||--o{ asset_movements : from_to_location
    employees ||--o{ asset_movements : from_to_employee
    departments ||--o{ asset_movements : from_to_department

    assets ||--o{ asset_maintenances : has
    suppliers ||--o{ asset_maintenances : serviced_by

    branches ||--o{ asset_stocktakes : performed_in
    asset_stocktakes ||--o{ asset_stocktake_items : contains
    assets ||--o{ asset_stocktake_items : checked

    fiscal_years ||--o{ asset_depreciation_runs : contains
    asset_depreciation_runs ||--o{ asset_depreciation_lines : contains
    assets ||--o{ asset_depreciation_lines : depreciated
    journal_entries ||--o{ asset_depreciation_runs : posted_as
```

---

## 3. Detail Tabel

### A. Master Aset

#### 1. `asset_categories`
Kategori aset untuk pengelompokan (mis. Kendaraan, IT Equipment, Mesin Produksi).

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `code` | String | Kode kategori (unique) |
| `name` | String | Nama kategori |
| `useful_life_months_default` | Integer | Default masa manfaat (opsional) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `code` (unique)

#### 2. `asset_models`
Template/model aset untuk standar spesifikasi (mis. "Laptop Dell Latitude 5xxx").

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `asset_category_id` | BigInt | FK -> `asset_categories` |
| `manufacturer` | String | Pabrikan (opsional) |
| `model_name` | String | Nama model |
| `specs` | JSON/Text | Spesifikasi ringkas (opsional) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `asset_category_id`

#### 3. `asset_locations`
Lokasi fisik yang lebih detail dari `branches`, mendukung hierarki (contoh: Cabang → Gedung → Lantai → Ruang).

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `branch_id` | BigInt | FK -> `branches` |
| `parent_id` | BigInt | Self FK -> `asset_locations` (nullable) |
| `code` | String | Kode lokasi (unique per branch) |
| `name` | String | Nama lokasi |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Unique Constraint (disarankan):** `(branch_id, code)`

**Index (disarankan):** `branch_id`, `parent_id`

#### 4. `assets`
Tabel utama aset.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `asset_code` | String | Kode aset (unique), mis. FA-000123 |
| `name` | String | Nama aset |
| `asset_model_id` | BigInt | FK -> `asset_models` (nullable) |
| `asset_category_id` | BigInt | FK -> `asset_categories` |
| `serial_number` | String | Nomor seri (nullable, index) |
| `barcode` | String | Barcode/QR payload (nullable, unique bila dipakai) |
| `branch_id` | BigInt | FK -> `branches` (lokasi cabang saat ini) |
| `asset_location_id` | BigInt | FK -> `asset_locations` (nullable) |
| `department_id` | BigInt | FK -> `departments` (nullable) |
| `employee_id` | BigInt | FK -> `employees` (nullable) |
| `supplier_id` | BigInt | FK -> `suppliers` (nullable) |
| `purchase_date` | Date | Tanggal perolehan |
| `purchase_cost` | Decimal(15,2) | Nilai perolehan |
| `currency` | String(3) | ISO currency (opsional; default sistem) |
| `warranty_end_date` | Date | Garansi berakhir (nullable) |
| `status` | Enum | `draft`, `active`, `maintenance`, `disposed`, `lost` |
| `condition` | Enum | `good`, `needs_repair`, `damaged` (opsional) |
| `notes` | Text | Catatan (nullable) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `asset_code` (unique), `status`, `asset_category_id`, `branch_id`, `department_id`, `employee_id`

##### Kolom Depresiasi (disimpan di `assets`)

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `depreciation_method` | Enum | `straight_line` (opsional: `declining_balance`) |
| `depreciation_start_date` | Date | Mulai depresiasi (biasanya sama dengan `purchase_date`) |
| `useful_life_months` | Integer | Masa manfaat |
| `salvage_value` | Decimal(15,2) | Nilai residu |
| `accumulated_depreciation` | Decimal(15,2) | Akumulasi depresiasi (cache) |
| `book_value` | Decimal(15,2) | Nilai buku saat ini (cache) |
| `depreciation_expense_account_id` | BigInt | FK -> `accounts` (nullable) |
| `accumulated_depr_account_id` | BigInt | FK -> `accounts` (nullable) |

> [!NOTE]
> `accumulated_depreciation` dan `book_value` adalah cache yang dihitung dari `asset_depreciation_lines`. Berguna untuk query/laporan cepat tanpa harus aggregate dari detail lines.

> [!TIP]
> Jika akun depresiasi berbeda per kategori, simpan default account mapping pada `asset_categories` (opsional) dan override per aset di `assets`.

---

### B. Pergerakan & Riwayat

#### 5. `asset_movements`
Audit trail untuk mutasi lokasi & penanggung jawab. Satu record merepresentasikan satu kejadian (transfer/assign/return/dispose/dsb).

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `asset_id` | BigInt | FK -> `assets` |
| `movement_type` | Enum | `acquired`, `transfer`, `assign`, `return`, `dispose`, `adjustment` |
| `moved_at` | Timestamp | Waktu kejadian |
| `from_branch_id` | BigInt | FK -> `branches` (nullable) |
| `to_branch_id` | BigInt | FK -> `branches` (nullable) |
| `from_location_id` | BigInt | FK -> `asset_locations` (nullable) |
| `to_location_id` | BigInt | FK -> `asset_locations` (nullable) |
| `from_department_id` | BigInt | FK -> `departments` (nullable) |
| `to_department_id` | BigInt | FK -> `departments` (nullable) |
| `from_employee_id` | BigInt | FK -> `employees` (nullable) |
| `to_employee_id` | BigInt | FK -> `employees` (nullable) |
| `reference` | String | No referensi dokumen internal (nullable) |
| `notes` | Text | Catatan (nullable) |
| `created_by` | BigInt | FK -> `users` (nullable) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `(asset_id, moved_at)`, `movement_type`, `to_branch_id`, `to_employee_id`

##### Penjelasan Tipe Pergerakan (`movement_type`)

| Tipe | Penjelasan | Contoh Kasus |
| :--- | :--- | :--- |
| **`acquired`** | Perolehan aset baru. Digunakan saat aset pertama kali didaftarkan. | Pembelian Laptop Dell via vendor PT. Maju Jaya, lokasi awal "Gudang IT". |
| **`transfer`** | Mutasi lokasi fisik antar cabang atau antar ruangan. | Memindahkan printer dari "Kantor Pusat" ke "Cabang Surabaya". |
| **`assign`** | Penugasan atau serah terima ke karyawan/departemen. | Memberikan laptop kepada karyawan baru (Budi) di Departemen IT. |
| **`return`** | Pengembalian aset dari penanggung jawab ke gudang/pool. | Karyawan mengembalikan laptop ke IT karena resign. |
| **`dispose`** | Pelepasan permanen dari operasional. | Menjual mobil operasional yang sudah melewati masa manfaat. |
| **`adjustment`** | Koreksi administratif setelah stocktake. | Mengoreksi lokasi printer dari "Lantai 1" ke "Lantai 2" setelah audit. |

> [!IMPORTANT]
> Saat aset dibuat, sistem otomatis membuat movement `acquired`. Saat lokasi/PIC diubah via form edit, sistem membuat movement yang sesuai. Saat disposal melalui Pipeline, aksi `create_record` otomatis membuat movement `dispose`.

---

### C. Pemeliharaan (Maintenance)

#### 6. `asset_maintenances`
Riwayat perawatan/servis aset.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `asset_id` | BigInt | FK -> `assets` |
| `maintenance_type` | Enum | `preventive`, `corrective`, `calibration`, `other` |
| `status` | Enum | `scheduled`, `in_progress`, `completed`, `cancelled` |
| `scheduled_at` | Timestamp | Jadwal (nullable) |
| `performed_at` | Timestamp | Realisasi (nullable) |
| `supplier_id` | BigInt | FK -> `suppliers` (nullable) |
| `cost` | Decimal(15,2) | Biaya (default 0) |
| `notes` | Text | Catatan (nullable) |
| `created_by` | BigInt | FK -> `users` (nullable) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `asset_id`, `status`, `maintenance_type`, `supplier_id`

##### Penjelasan Tipe Maintenance

| Tipe | Penjelasan | Contoh |
| :--- | :--- | :--- |
| **`preventive`** | Perawatan berkala terjadwal untuk mencegah kerusakan. | Servis kendaraan tiap 3 bulan, cleaning AC. |
| **`corrective`** | Perbaikan karena kerusakan. | Ganti layar laptop rusak, perbaikan mesin. |
| **`calibration`** | Kalibrasi alat ukur atau instrumen. | Kalibrasi timbangan, alat uji laboratorium. |
| **`other`** | Jenis perawatan lainnya. | Upgrade hardware, modifikasi mesin. |

---

### D. Stocktake (Inventarisasi)

#### 7. `asset_stocktakes`
Dokumen stocktake per cabang/per periode.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `branch_id` | BigInt | FK -> `branches` |
| `reference` | String | No dokumen (unique per branch, disarankan) |
| `planned_at` | Timestamp | Jadwal |
| `performed_at` | Timestamp | Realisasi (nullable) |
| `status` | Enum | `draft`, `in_progress`, `completed`, `cancelled` |
| `created_by` | BigInt | FK -> `users` (nullable) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `branch_id`, `status`

#### 8. `asset_stocktake_items`
Hasil pengecekan aset pada stocktake.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `asset_stocktake_id` | BigInt | FK -> `asset_stocktakes` |
| `asset_id` | BigInt | FK -> `assets` |
| `expected_branch_id` | BigInt | FK -> `branches` (nullable) |
| `expected_location_id` | BigInt | FK -> `asset_locations` (nullable) |
| `found_branch_id` | BigInt | FK -> `branches` (nullable) |
| `found_location_id` | BigInt | FK -> `asset_locations` (nullable) |
| `result` | Enum | `found`, `missing`, `damaged`, `moved` |
| `notes` | Text | Catatan (nullable) |
| `checked_at` | Timestamp | Waktu cek (nullable) |
| `checked_by` | BigInt | FK -> `users` (nullable) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Index (disarankan):** `asset_stocktake_id`, `asset_id`, `result`

**Unique Constraint (disarankan):** `(asset_stocktake_id, asset_id)`

> [!NOTE]
> Saat stocktake selesai dan ditemukan perbedaan lokasi (`result = moved`), admin bisa membuat `asset_movement` dengan `movement_type = adjustment` untuk mengoreksi data lokasi aset secara otomatis.

---

### E. Depresiasi & Posting Akuntansi

#### 9. `asset_depreciation_runs`
Header perhitungan depresiasi per periode (biasanya bulanan).

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `fiscal_year_id` | BigInt | FK -> `fiscal_years` |
| `period_start` | Date | Awal periode |
| `period_end` | Date | Akhir periode |
| `status` | Enum | `draft`, `calculated`, `posted`, `void` |
| `journal_entry_id` | BigInt | FK -> `journal_entries` (nullable) |
| `created_by` | BigInt | FK -> `users` (nullable) |
| `posted_by` | BigInt | FK -> `users` (nullable) |
| `posted_at` | Timestamp | (nullable) |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Unique Constraint (disarankan):** `(fiscal_year_id, period_start, period_end)`

**Index (disarankan):** `fiscal_year_id`, `status`

##### Penjelasan Status Depreciation Run

| Status | Penjelasan |
| :--- | :--- |
| **`draft`** | Run baru dibuat, belum dihitung. |
| **`calculated`** | Depresiasi sudah dihitung per aset, menunggu posting. |
| **`posted`** | Sudah diposting ke jurnal akuntansi, jurnal terkait di-link. |
| **`void`** | Dibatalkan (reverse jurnal jika sebelumnya posted). |

#### 10. `asset_depreciation_lines`
Detail depresiasi per aset untuk satu periode.

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | BigInt | Primary Key |
| `asset_depreciation_run_id` | BigInt | FK -> `asset_depreciation_runs` |
| `asset_id` | BigInt | FK -> `assets` |
| `amount` | Decimal(15,2) | Nominal depresiasi periode ini |
| `accumulated_before` | Decimal(15,2) | Akumulasi sebelum periode |
| `accumulated_after` | Decimal(15,2) | Akumulasi sesudah periode |
| `book_value_after` | Decimal(15,2) | Nilai buku setelah periode |
| `created_at` | Timestamp | |
| `updated_at` | Timestamp | |

**Unique Constraint (disarankan):** `(asset_depreciation_run_id, asset_id)`

**Index (disarankan):** `asset_depreciation_run_id`, `asset_id`

---

## 4. Aturan Bisnis (Ringkas)

### Status Aset
*   `draft`: belum aktif dipakai (data belum lengkap/menunggu verifikasi).
*   `active`: aset aktif dan dapat ikut depresiasi.
*   `maintenance`: sementara tidak tersedia (tetap bisa depresiasi sesuai kebijakan).
*   `disposed`: dilepas/dijual/dihapus; stop depresiasi setelah tanggal disposal.
*   `lost`: hilang; perlakuan akuntansi mengikuti kebijakan (mis. write-off).

> [!IMPORTANT]
> Status aset di-manage oleh Pipeline System (`10_pipeline_design.md`). Kolom `assets.status` di-sync otomatis melalui aksi `update_field` pada pipeline transition. Lihat contoh Asset Lifecycle di Pipeline design.

### Depresiasi Bulanan (Straight Line)
Nominal per bulan (dengan pembulatan kebijakan sistem):
```
(purchase_cost - salvage_value) / useful_life_months
```

### Posting Akuntansi Depresiasi
Saat `asset_depreciation_runs` diposting:
*   Debit: `depreciation_expense_account_id`
*   Kredit: `accumulated_depr_account_id`

> [!NOTE]
> Saat posting, sistem membuat satu `journal_entry` header dan line agregat per akun. Kolom `journal_entry_id` di `asset_depreciation_runs` di-link ke jurnal yang dibuat.

---

## 5. Rekomendasi Menu & Tabel Terlibat

Bagian ini merangkum menu yang dibutuhkan untuk menjalankan modul manajemen aset end-to-end.

### A. Master Data (Setup)

#### 1) Asset Categories
Tujuan: mengelola kategori aset dan default masa manfaat.

Jenis menu: Simple CRUD
Agent skill: `feature-crud-simple`

Tabel terlibat:
* `asset_categories`

#### 2) Asset Models
Tujuan: mengelola template/model aset (spesifikasi standar).

Jenis menu: Complex CRUD
Agent skill: `feature-crud-complex`

Tabel terlibat:
* `asset_models`
* `asset_categories`

#### 3) Asset Locations
Tujuan: mengelola lokasi fisik aset per cabang dengan hierarki.

Jenis menu: Complex CRUD
Agent skill: `feature-crud-complex`

Tabel terlibat:
* `asset_locations`
* `branches`

---

### B. Operasional Aset

#### 4) Assets (List & Form)
Tujuan: registrasi aset baru, edit data aset, dan mengelola "current state" (lokasi, PIC, status).

Jenis menu: Complex CRUD
Agent skill: `feature-crud-complex`

Tabel terlibat:
* `assets`
* `asset_categories`
* `asset_models`
* `asset_locations`
* `branches`
* `departments`
* `employees`
* `suppliers`
* `accounts` (untuk `depreciation_expense_account_id` dan `accumulated_depr_account_id`)

Catatan proses:
* Saat aset dibuat/diakuisisi, sistem menambah movement `acquired` di `asset_movements`.
* Saat data utama aset (lokasi, PIC) diupdate, sistem menyelaraskan movement terkait.
* Saat lokasi/PIC berubah, update kolom di `assets` dan simpan histori di `asset_movements`.

#### 5) Asset Detail (Profile)
Tujuan: melihat ringkasan aset + tab histori (movement, maintenance, stocktake, depresiasi).

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `assets`
* `asset_movements`
* `asset_maintenances`
* `asset_stocktake_items` (dan header `asset_stocktakes`)
* `asset_depreciation_lines` (dan header `asset_depreciation_runs`)
* `branches`, `asset_locations`, `departments`, `employees`, `suppliers`, `accounts`

#### 6) Asset Movements (Transfer / Assignment)
Tujuan: membuat dokumen perpindahan aset dan audit trail.

Jenis menu: Complex CRUD
Agent skill: `feature-crud-complex`

Tabel terlibat:
* `asset_movements`
* `assets`
* `branches`
* `asset_locations`
* `departments`
* `employees`
* `users` (kolom `created_by`)

#### 7) Asset Maintenance
Tujuan: mencatat jadwal/riwayat perawatan dan biaya.

Jenis menu: Complex CRUD
Agent skill: `feature-crud-complex`

Tabel terlibat:
* `asset_maintenances`
* `assets`
* `suppliers`
* `users` (kolom `created_by`)

---

### C. Kontrol & Audit

#### 8) Asset Stocktake (Header)
Tujuan: membuat event stocktake per cabang/per periode.

Jenis menu: Complex CRUD
Agent skill: `feature-crud-complex`

Tabel terlibat:
* `asset_stocktakes`
* `branches`
* `users` (kolom `created_by`)

#### 9) Asset Stocktake (Items)
Tujuan: mengisi hasil cek per aset dan mencatat selisih.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `asset_stocktake_items`
* `asset_stocktakes`
* `assets`
* `branches`
* `asset_locations`
* `users` (kolom `checked_by`)

---

### D. Akuntansi (Depresiasi)

#### 10) Depreciation Run (Calculate)
Tujuan: memilih periode, menghitung depresiasi untuk aset yang eligible.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `asset_depreciation_runs`
* `asset_depreciation_lines`
* `assets`
* `fiscal_years`

#### 11) Depreciation Run (Post to Journal)
Tujuan: posting hasil depresiasi menjadi jurnal akuntansi.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `asset_depreciation_runs` (kolom `journal_entry_id`, `posted_by`, `posted_at`)
* `asset_depreciation_lines`
* `assets` (referensi akun depresiasi per aset)
* `accounts`
* `journal_entries`
* `journal_entry_lines`
* `users`

---

### E. Laporan (Opsional tapi umum)

#### 12) Asset Register / Asset Listing Report
Tujuan: daftar aset lengkap + filter.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `assets`
* `asset_categories`, `asset_models`
* `branches`, `asset_locations`
* `departments`, `employees`
* `suppliers`

#### 13) Book Value & Depreciation Report
Tujuan: laporan nilai buku dan depresiasi per periode/per aset.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `assets`
* `asset_depreciation_runs`, `asset_depreciation_lines`
* `fiscal_years`
* `accounts`

#### 14) Maintenance Cost Report
Tujuan: biaya maintenance per aset/per periode/per vendor.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `asset_maintenances`
* `assets`
* `suppliers`

#### 15) Stocktake Variance Report
Tujuan: daftar aset missing/damaged/moved pada suatu stocktake.

Jenis menu: Non-CRUD
Agent skill: `feature-non-crud`

Tabel terlibat:
* `asset_stocktakes`
* `asset_stocktake_items`
* `assets`
* `branches`, `asset_locations`

---

## 6. Integrasi dengan Pipeline & Approval System

### Asset Lifecycle (via Pipeline)

```mermaid
stateDiagram-v2
    [*] --> draft
    draft --> active : Activate
    draft --> cancelled : Cancel
    active --> maintenance : Send to Maintenance
    maintenance --> active : Return from Maintenance
    active --> disposed : Dispose (requires approval + comment)
    active --> lost : Mark as Lost (requires comment)
    disposed --> [*]
    lost --> [*]
    cancelled --> [*]
```

**Pipeline:** `asset_lifecycle`
- entity_type: `App\Models\Asset`

| State (code) | Name | Type | Color |
| :--- | :--- | :--- | :--- |
| `draft` | Draft | initial | `#6B7280` |
| `active` | Active | intermediate | `#10B981` |
| `maintenance` | In Maintenance | intermediate | `#F59E0B` |
| `disposed` | Disposed | final | `#EF4444` |
| `lost` | Lost | final | `#DC2626` |
| `cancelled` | Cancelled | final | `#9CA3AF` |

| Transisi | From → To | Permission | Guard | Actions |
| :--- | :--- | :--- | :--- | :--- |
| Activate | `draft` → `active` | `assets.activate` | `purchase_cost > 0` | Update `assets.status` |
| Cancel | `draft` → `cancelled` | `assets.cancel` | — | Update `assets.status` |
| Send to Maintenance | `active` → `maintenance` | `assets.manage` | — | Update `assets.status`, Notify asset manager |
| Return from Maintenance | `maintenance` → `active` | `assets.manage` | — | Update `assets.status` |
| Dispose | `active` → `disposed` | `assets.dispose` | Requires approval, Requires comment | Update `assets.status`, Create `asset_movement` (type: dispose), Trigger approval |
| Mark as Lost | `active` → `lost` | `assets.manage` | Requires comment | Update `assets.status`, Create `asset_movement` (type: lost) |

> [!NOTE]
> Untuk detail lengkap tentang pipeline configuration (states, transitions, guards, actions), lihat `10_pipeline_design.md`. Untuk detail approval flow pada disposal aset, lihat `11_approval_design.md`.
