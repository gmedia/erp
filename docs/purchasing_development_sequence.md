# Urutan Pembuatan Menu Purchasing ERP

## Pendahuluan
Dokumen ini menjelaskan urutan sistematis pembuatan menu dan halaman untuk modul Purchasing ERP, dari yang paling fundamental hingga advanced features. Setiap menu dilengkapi dengan:
- Penjelasan fungsi dalam Bahasa Indonesia
- Fitur-fitur utama
- Tabel database yang terlibat
- Validasi dan business rules
- Estimasi waktu pengerjaan

---

## FASE 1: MASTER DATA & FOUNDATION
**Durasi**: Minggu 1-4 (4 minggu)  
**Tujuan**: Membangun fondasi data master yang dibutuhkan semua transaksi

---

### 1. Master Data - Warehouses (Gudang)
**Urutan**: #1  
**Prioritas**: ⭐ CRITICAL  
**Estimasi**: 2-3 hari

#### Penjelasan
Halaman untuk mengelola data gudang tempat penyimpanan barang. Gudang adalah lokasi tujuan pengiriman barang yang dibeli. Harus dibuat pertama kali karena akan direferensikan oleh Purchase Request, Purchase Order, dan Goods Receipt.

#### Fungsi Utama
- Menyimpan informasi gudang perusahaan
- Mendefinisikan lokasi penerimaan dan penyimpanan barang
- Mengatur PIC (Person In Charge) per gudang
- Aktivasi/deaktivasi gudang

#### Fitur yang Harus Dibuat
1. **List Gudang**
   - Tabel dengan kolom: Kode Gudang, Nama Gudang, Tipe, Alamat, Kota, PIC, Status
   - Filter berdasarkan tipe dan status (aktif/non-aktif)
   - Pencarian berdasarkan kode atau nama
   - Pagination untuk daftar panjang

2. **Form Tambah/Edit Gudang**
   - Input: Kode Gudang (unique, auto-generate atau manual)
   - Input: Nama Gudang
   - Dropdown: Tipe Gudang (Main, Transit, Production, Retail)
   - Textarea: Alamat lengkap
   - Input: Kota, Provinsi
   - Input: PIC Name, PIC Phone
   - Toggle: Status Aktif/Non-aktif
   - Button: Simpan, Batal

3. **View Detail Gudang**
   - Informasi lengkap gudang
   - Riwayat transaksi terkait (PR, PO, GR)
   - Button: Edit, Aktifkan/Nonaktifkan

4. **Hapus Gudang**
   - Soft delete (ubah status jadi non-aktif)
   - Validasi: tidak bisa dihapus jika ada transaksi terkait

#### Tabel Database yang Terlibat
- **`warehouses`** (tabel utama)
  - Operasi: SELECT, INSERT, UPDATE, (soft) DELETE
  - Primary Key: `warehouse_id`
  - Unique: `warehouse_code`
  - Index: `warehouse_code`, `warehouse_name`, `is_active`

#### Validasi
- Kode gudang harus unique (tidak boleh duplikat)
- Nama gudang wajib diisi
- Kota dan alamat wajib diisi
- Tidak bisa dihapus jika sudah ada PR/PO/GR yang terkait
- PIC phone harus format nomor telepon yang valid

#### Business Rules
- Satu perusahaan minimal punya 1 gudang aktif
- Gudang non-aktif tidak bisa dipilih di transaksi baru
- Gudang yang sudah punya transaksi tidak bisa dihapus permanent

---

### 2. Master Data - Item Categories (Kategori Barang)
**Urutan**: #2  
**Prioritas**: ⭐ CRITICAL  
**Estimasi**: 2-3 hari

#### Penjelasan
Halaman untuk mengelola kategori atau klasifikasi barang. Kategori membantu mengelompokkan item untuk memudahkan pencarian, pelaporan, analisis pembelian, dan pengaturan approval rules. Sistem mendukung hierarki kategori (parent-child).

#### Fungsi Utama
- Mengorganisir barang dalam kategori dan sub-kategori
- Memudahkan pencarian dan filtering barang
- Mendukung pelaporan berdasarkan kategori
- Mendukung approval rules berdasarkan kategori

#### Fitur yang Harus Dibuat
1. **List Kategori (Tree View)**
   - Tampilan hierarki parent-child
   - Expand/collapse untuk melihat sub-kategori
   - Icon untuk parent dan child
   - Action: Edit, Delete, Add Sub-Category

2. **Form Tambah/Edit Kategori**
   - Input: Kode Kategori (unique)
   - Input: Nama Kategori
   - Dropdown: Parent Category (optional, untuk sub-kategori)
   - Textarea: Deskripsi
   - Toggle: Status Aktif/Non-aktif
   - Button: Simpan, Batal

3. **Tree Selector**
   - Component reusable untuk select category di form lain
   - Search dalam tree
   - Breadcrumb navigation (Parent > Child > Grandchild)

#### Tabel Database yang Terlibat
- **`item_categories`** (tabel utama)
  - Operasi: SELECT, INSERT, UPDATE, DELETE
  - Primary Key: `category_id`
  - Foreign Key: `parent_category_id` → self-reference ke `category_id`
  - Unique: `category_code`

#### Validasi
- Kode kategori harus unique
- Nama kategori wajib diisi
- Parent category tidak boleh sama dengan dirinya sendiri (circular reference)
- Tidak bisa dihapus jika sudah ada item yang menggunakan kategori ini
- Tidak bisa set dirinya sendiri sebagai parent

#### Business Rules
- Depth hierarki maksimal 5 level
- Kategori yang punya child tidak bisa dihapus
- Kategori non-aktif tidak muncul di dropdown selection

---

### 3. Master Data - Items (Barang/Item)
**Urutan**: #3  
**Prioritas**: ⭐ CRITICAL  
**Estimasi**: 4-5 hari

#### Penjelasan
Halaman untuk mengelola master data barang yang akan dibeli perusahaan. Ini adalah jantung dari sistem purchasing karena semua transaksi (PR, PO, GR) akan mereferensi item ini. Item bisa berupa raw material, finished goods, spare parts, atau consumables.

#### Fungsi Utama
- Menyimpan informasi lengkap tentang setiap item yang dibeli
- Mendefinisikan spesifikasi, satuan, dan harga item
- Mengatur stok minimum, maksimum, dan reorder point
- Track harga pembelian terakhir untuk perbandingan

#### Fitur yang Harus Dibuat
1. **List Items**
   - Tabel dengan kolom: Kode Item, Nama, Kategori, Tipe, UoM, Current Stock, Reorder Point, Last Price, Status
   - Filter:
     - Kategori (dropdown tree)
     - Tipe Item (Raw Material, Finished Goods, Spare Part, Consumable)
     - Status (Aktif/Non-aktif)
     - Stock Status (Below Reorder, Normal, Overstock)
   - Pencarian: Kode atau Nama item
   - Indikator warna: Merah jika stock < reorder point
   - Sort: semua kolom
   - Pagination

2. **Form Tambah/Edit Item**
   - **Section: Informasi Umum**
     - Input: Kode Item (unique, auto atau manual)
     - Input: Nama Item
     - Textarea: Deskripsi
     - Dropdown Tree: Kategori
     - Dropdown: Tipe Item
     - Dropdown: Unit of Measure (PCS, KG, M, BOX, dll)
   
   - **Section: Inventory Control**
     - Input Number: Minimum Stock
     - Input Number: Reorder Point (safety stock level)
     - Input Number: Maximum Stock
   
   - **Section: Pricing**
     - Input Currency: Standard Price (harga standar/budget)
     - Display: Last Purchase Price (auto dari PO terakhir, read-only)
   
   - **Section: Spesifikasi**
     - Textarea: Spesifikasi teknis
     - Upload: Gambar item (multiple)
     - Upload: Technical docs (datasheet, dll)
   
   - Toggle: Status Aktif/Non-aktif
   - Button: Simpan, Batal

3. **View Detail Item**
   - Tab View dengan 6 tab:
     - **General Info**: Semua informasi dasar
     - **Specifications**: Spesifikasi teknis, dokumen, gambar
     - **Inventory**: Stock per gudang, stock movement
     - **Pricing**: Price history, price trend chart
     - **Suppliers**: Daftar supplier untuk item ini, last price per supplier
     - **Purchase History**: PR/PO history untuk item ini

4. **Bulk Import Items**
   - Download Excel template
   - Upload Excel file
   - Validation & preview
   - Confirm import
   - Error handling dengan detail error per row

5. **Bulk Export Items**
   - Export filtered items ke Excel
   - Include semua data atau selected columns

6. **Item Quick Actions**
   - Quick reorder: Generate PR untuk items below reorder point
   - Mass price update
   - Mass category change

#### Tabel Database yang Terlibat
- **`items`** (tabel utama)
  - Operasi: SELECT, INSERT, UPDATE, (soft) DELETE
  - Join dengan: `item_categories`
  - Index: `item_code`, `item_name`, `category_id`, `is_active`

- **`item_categories`**
  - Operasi: SELECT (untuk dropdown dan display)

- **`purchase_order_items`**
  - Operasi: SELECT (untuk get last purchase price)

#### Validasi
- Kode item harus unique
- Nama item wajib diisi
- UoM (Unit of Measure) wajib diisi
- Minimum Stock ≤ Reorder Point ≤ Maximum Stock
- Standard Price ≥ 0
- Item tidak bisa dihapus jika sudah ada di PR/PO/GR
- Format gambar: JPG, PNG, WebP (max 2MB per file)

#### Business Rules
- Last Purchase Price auto-update setelah PO approved
- Stock levels hanya informational di purchasing, actual stock di inventory module
- Item non-aktif tidak muncul di autocomplete saat buat PR/PO
- Reorder point trigger untuk stock replenishment PR (auto-generate)

---

### 4. Master Data - Suppliers (Pemasok)
**Urutan**: #4  
**Prioritas**: ⭐ CRITICAL  
**Estimasi**: 4-5 hari

#### Penjelasan
Halaman untuk mengelola data supplier atau vendor yang memasok barang ke perusahaan. Informasi supplier sangat penting untuk pembuatan PO, evaluasi kinerja supplier, dan manajemen hubungan dengan pemasok.

#### Fungsi Utama
- Menyimpan informasi lengkap supplier (kontak, alamat, bank, pajak)
- Mengatur term of payment per supplier
- Rating dan evaluasi supplier
- Manage multiple contact persons per supplier
- Track purchase history dan performance

#### Fitur yang Harus Dibuat
1. **List Suppliers**
   - Tabel dengan kolom: Kode Supplier, Nama, Tipe, Contact Person, Telepon, Email, Kota, Rating, Status
   - Filter:
     - Status (Aktif/Non-aktif)
     - Tipe (Local/International)
     - Kota/Provinsi
     - Rating (1-5 bintang)
   - Pencarian: Nama atau Kode
   - Tag/Label: Good, Preferred, Need Improvement, Blacklist
   - Sort: Rating, Total Purchase, dll

2. **Form Tambah/Edit Supplier**
   - **Section: Company Information**
     - Input: Kode Supplier (unique, auto atau manual)
     - Input: Nama Perusahaan
     - Dropdown: Tipe (Local/International)
     - Input: NPWP (Tax ID)
   
   - **Section: Contact Information**
     - Input: Contact Person Name
     - Input: Email (format validation)
     - Input: Phone
     - Input: Mobile
   
   - **Section: Address**
     - Textarea: Alamat lengkap
     - Input: Kota
     - Input: Provinsi
     - Input: Kode Pos
     - Dropdown: Negara (default Indonesia)
   
   - **Section: Payment & Banking**
     - Input Number: Payment Term Days (default 30)
     - Dropdown: Currency (IDR, USD, EUR)
     - Input: Bank Name
     - Input: Bank Account Number
     - Input: Bank Account Name
   
   - **Section: Rating & Status**
     - Star Rating: 1-5 (untuk evaluasi)
     - Toggle: Status Aktif/Non-aktif
   
   - Button: Simpan, Batal

3. **View Detail Supplier**
   - Tab View dengan 6 tab:
     - **General**: Info umum supplier
     - **Contact Persons**: 
       - List contact persons (Name, Position, Phone, Email, IsPrimary)
       - Add/Edit/Delete contact
       - Set primary contact
     - **Items Supplied**:
       - Tabel item yang biasa disupply supplier ini
       - Last price, Last PO date, Lead time
     - **Purchase History**:
       - Tabel semua PO ke supplier ini
       - Total spend (this year, last year)
       - Chart: Spending trend 12 bulan
     - **Performance Metrics**:
       - On-Time Delivery Rate (%)
       - Quality Acceptance Rate (%)
       - Average Lead Time (days)
       - Response Time
       - Overall Performance Score
       - Chart: Performance trend
     - **Documents**:
       - Upload company profile, SIUP, TDP, Tax docs
       - Certificates (ISO, dll)
       - Contracts
       - Download/preview documents

4. **Supplier Performance Dashboard**
   - Ranking semua supplier by performance score
   - Filter by period
   - Export performance report

5. **Supplier Quick Actions**
   - Activate/Deactivate
   - Add to Preferred List
   - Block/Blacklist (dengan alasan)
   - Export Supplier Card (PDF)

#### Tabel Database yang Terlibat
- **`suppliers`** (tabel utama)
  - Operasi: SELECT, INSERT, UPDATE, (soft) DELETE
  - Index: `supplier_code`, `supplier_name`, `is_active`

- **`document_attachments`**
  - Operasi: SELECT, INSERT, DELETE
  - Filter: `document_type` = 'SUPPLIER', `document_id` = supplier_id

- **`purchase_orders`**
  - Operasi: SELECT (untuk history dan performance)
  - Join untuk analisis spending

- **`goods_receipts` & `goods_receipt_items`**
  - Operasi: SELECT (untuk calculate performance metrics)

#### Validasi
- Kode supplier harus unique
- Nama supplier wajib diisi
- Email harus format valid (xxx@yyy.zzz)
- Phone/Mobile harus angka
- Payment term days harus > 0
- NPWP format: XX.XXX.XXX.X-XXX.XXX (untuk Indonesia)
- Bank account number harus angka
- Supplier tidak bisa dihapus jika sudah ada PO
- Rating harus 1-5

#### Business Rules
- Supplier non-aktif tidak bisa dipilih di PO baru
- Supplier dengan rating < 2 muncul warning saat dipilih di PO
- Preferred supplier muncul di urutan teratas di autocomplete
- Performance metrics auto-calculate setiap akhir bulan
- Rating bisa manual input atau auto-calculate dari performance

---

### 5. Settings - Document Numbering (Penomoran Dokumen)
**Urutan**: #5  
**Prioritas**: ⭐ HIGH  
**Estimasi**: 2-3 hari

#### Penjelasan
Halaman untuk mengatur format penomoran otomatis untuk dokumen PR, PO, dan GR. Sistem akan generate nomor urut secara otomatis sesuai format yang ditentukan admin. Penomoran yang konsisten penting untuk tracking, filing, dan audit.

#### Fungsi Utama
- Mengatur format nomor dokumen otomatis
- Generate nomor sequential yang unik
- Support berbagai format (dengan tanggal, prefix, suffix)
- Reset counter per periode

#### Fitur yang Harus Dibuat
1. **Setting Format Nomor**
   - 3 Section terpisah untuk PR, PO, GR
   - Setiap section punya:
     - **Prefix**: Input text (contoh: PR-, PO/, GR-)
     - **Date Format**: Dropdown
       - YYYY (2025)
       - YYYYMM (202502)
       - YYMMDD (250208)
       - YY/MM (25/02)
       - MM/YYYY (02/2025)
       - None (tidak pakai tanggal)
     - **Separator**: Input (-, /, . atau kosong)
     - **Sequence Length**: Dropdown (3-6 digit)
       - 3 digit (###) → 001, 002, ..., 999
       - 4 digit (####) → 0001, 0002, ..., 9999
       - 5 digit (#####) → 00001, ..., 99999
     - **Suffix**: Input text (optional)
     - **Preview**: Show preview dengan contoh
       - Example: PR-202502-0001
     - **Reset Period**: Dropdown
       - Yearly (reset tiap tahun)
       - Monthly (reset tiap bulan)
       - Never (running number terus)

2. **Toggle Auto-Numbering**
   - ON: Sistem auto-generate
   - OFF: User input manual (tetap harus unique)

3. **Current Counter Display**
   - Show current number untuk PR, PO, GR
   - Next number yang akan di-generate
   - Last reset date

4. **Manual Counter Reset**
   - Button: Reset Counter Now
   - Confirmation dialog
   - Log reset action (who, when)

#### Tabel Database yang Terlibat
- **`purchasing_settings`**
  - Operasi: SELECT, UPDATE
  - Setting keys yang disimpan:
    - `pr_number_format` (contoh value: "PR-{YYYYMM}-{####}")
    - `pr_auto_numbering` (TRUE/FALSE)
    - `pr_current_sequence` (angka terakhir)
    - `pr_reset_period` (yearly/monthly/never)
    - `pr_last_reset_date`
    - Similar untuk PO dan GR

#### Validasi
- Format harus valid (bisa di-parse)
- Sequence minimal 3 digit
- Preview harus tampil dengan benar
- Manual number (jika auto OFF) harus unique

#### Business Rules
- Saat auto-numbering ON, tidak bisa input manual
- Sequence auto-increment saat create document baru
- Reset counter:
  - Yearly: reset 1 Jan jam 00:00
  - Monthly: reset tanggal 1 jam 00:00
- Number generation harus thread-safe (no duplicate saat concurrent access)
- Format changes hanya affect dokumen baru, tidak ubah dokumen lama

#### Logic Generate Number
```
Function generatePRNumber():
    1. Get format dari settings
    2. Parse format: Prefix + DatePart + Separator + Sequence + Suffix
    3. Increment current_sequence
    4. Check if need reset (based on reset_period)
    5. Format sequence dengan padding zeros
    6. Concat semua part
    7. Update current_sequence di database
    8. Return generated number
```

---

### 6. Settings - General Settings (Pengaturan Umum)
**Urutan**: #6  
**Prioritas**: 🔵 MEDIUM  
**Estimasi**: 2-3 hari

#### Penjelasan
Halaman untuk konfigurasi pengaturan umum sistem purchasing seperti currency default, tax rate, notifikasi email, dan berbagai aturan bisnis. Settings ini mempengaruhi behavior seluruh modul purchasing.

#### Fungsi Utama
- Set default values untuk transaksi
- Atur business rules dan validations
- Configure notifications
- System preferences

#### Fitur yang Harus Dibuat
1. **System Settings Section**
   - **Default Currency**: Dropdown (IDR, USD, EUR, SGD, dll)
   - **Default Tax Rate (PPN)**: Input number (default 11%)
   - **Date Format**: Dropdown (DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD)
   - **Number Format**: Dropdown
     - 1,000,000.00 (International)
     - 1.000.000,00 (Indonesia)
   - **Timezone**: Dropdown (Asia/Jakarta, dll)

2. **Transaction Settings Section**
   - **Allow Backdated Transactions**: Toggle (Yes/No)
     - Jika Yes: Bisa input tanggal sebelum hari ini
     - Jika No: Tanggal harus >= hari ini
   
   - **Allow Over-Receipt**: Toggle (Yes/No)
     - Jika Yes: GR qty bisa > PO qty
     - Jika No: GR qty tidak boleh > PO qty
   
   - **Require Attachment**: Checkboxes
     - [ ] PR harus ada attachment
     - [ ] PO harus ada attachment
     - [ ] GR harus ada attachment
   
   - **Default Payment Terms**: Input number (hari, default 30)
   
   - **Tolerance Over-Delivery**: Input number (%, default 5%)
     - GR qty bisa exceed PO qty hingga X%
   
   - **Tolerance Under-Delivery**: Input number (%, default 5%)
     - GR qty bisa kurang dari PO qty hingga X%

3. **Notification Settings Section**
   - **Email Notification**: Master toggle ON/OFF
   
   - **Event Notifications**: Checkboxes untuk setiap event
     - [ ] PR Submitted (kirim ke approver)
     - [ ] PR Approved (kirim ke requester)
     - [ ] PR Rejected (kirim ke requester)
     - [ ] PO Created (kirim ke buyer)
     - [ ] PO Approved (kirim ke buyer & supplier)
     - [ ] PO Sent to Supplier
     - [ ] Delivery Reminder (3 hari sebelum delivery date)
     - [ ] Overdue PO (saat melewati delivery date)
     - [ ] GR Created (kirim ke QC)
     - [ ] GR Quality Issue (kirim ke buyer & manager)
   
   - **Notification Recipients**: Per event, set penerima
     - Dropdown: Specific user, Role, Dynamic (from document)

4. **Integration Settings Section** (jika ada integrasi)
   - **Inventory Module**: Toggle (Enabled/Disabled)
     - Jika enabled: GR posted auto update stock
   
   - **Finance Module**: Toggle
     - Jika enabled: PO approved auto create budget reservation
   
   - **API Settings**: (untuk 3rd party integration)
     - API Key generation
     - Webhook URLs

5. **Security Settings Section**
   - **Session Timeout**: Input number (menit, default 60)
   - **Password Policy**: Checkboxes
     - [ ] Require uppercase
     - [ ] Require lowercase
     - [ ] Require number
     - [ ] Require special char
     - Minimum length: Input number (default 8)
   - **Audit Log Retention**: Input number (hari, default 365)

6. **Action Buttons**
   - Simpan Perubahan
   - Reset ke Default
   - Test Email (kirim test notification)

#### Tabel Database yang Terlibat
- **`purchasing_settings`**
  - Operasi: SELECT, UPDATE
  - Multiple rows dengan key-value pairs
  - Contoh records:
```sql
INSERT INTO purchasing_settings (setting_key, setting_value) VALUES
('default_currency', 'IDR'),
('default_tax_rate', '11.00'),
('allow_backdated', 'false'),
('allow_over_receipt', 'true'),
('tolerance_over_delivery', '5.00'),
('default_payment_terms', '30'),
('email_notification_enabled', 'true'),
('session_timeout', '60');
```

#### Validasi
- Tax rate: 0-100
- Payment term days: > 0
- Tolerance percentage: 0-100
- Session timeout: 5-240 menit
- Email format valid untuk notification recipients
- Password policy: minimal length ≥ 6

#### Business Rules
- Changes apply to new transactions, tidak affect existing
- Some settings require admin privilege untuk ubah
- Setting changes di-log untuk audit trail
- Test email harus berhasil untuk validate SMTP configuration

---

## FASE 2: PURCHASE REQUEST (PR)
**Durasi**: Minggu 5-7 (3 minggu)  
**Tujuan**: Membangun modul permintaan pembelian

---

### 7. Purchase Request - List (Daftar PR)
**Urutan**: #7  
**Prioritas**: ⭐ CRITICAL  
**Estimasi**: 3-4 hari

#### Penjelasan
Halaman untuk menampilkan daftar semua Purchase Request (PR) yang telah dibuat. PR adalah dokumen permintaan pembelian barang yang diajukan oleh user atau department. Halaman ini adalah entry point utama untuk melihat, mencari, dan mengelola semua PR.

#### Fungsi Utama
- Menampilkan semua PR dengan informasi ringkas
- Filter dan pencarian PR
- Quick access ke fungsi-fungsi PR (view, edit, approve, dll)
- Monitor status PR

#### Fitur yang Harus Dibuat
1. **Tabel Daftar PR**
   - Kolom yang ditampilkan:
     - **PR Number** (link ke detail, contoh: PR-202502-0001)
     - **PR Date** (tanggal dibuat)
     - **Requester** (nama user yang request)
     - **Department** (department peminta)
     - **Total Items** (jumlah item dalam PR)
     - **Total Amount** (estimasi total nilai)
     - **Status** (badge dengan warna)
     - **Required Date** (tanggal dibutuhkan)
     - **Actions** (tombol aksi)
   
   - Status Badge dengan warna:
     - **Draft**: Abu-abu
     - **Submitted**: Biru
     - **Approved**: Hijau
     - **Rejected**: Merah
     - **Partially Ordered**: Orange (sebagian sudah jadi PO)
     - **Fully Ordered**: Hijau Tua (semua sudah jadi PO)
     - **Cancelled**: Hitam

2. **Filter Panel** (di atas atau samping tabel)
   - **Date Range**: Dari tanggal - Sampai tanggal (default: bulan ini)
   - **Status**: Multi-select dropdown
   - **Requester**: Autocomplete user
   - **Department**: Dropdown
   - **Priority**: Low, Normal, High, Critical
   - **Required Date Range**: Filter by tanggal dibutuhkan
   - **Search**: Input box untuk cari PR Number
   - Button: Apply Filter, Reset Filter

3. **Quick Filter Buttons** (di atas tabel)
   - My PR (PR yang dibuat user login)
   - Pending Approval (status = Submitted)
   - Approved (status = Approved)
   - Urgent (priority = High atau Critical)
   - Due Soon (required date < 7 hari)

4. **Action Buttons Utama**
   - **+ Create New PR**: Buat PR baru
   - **Export to Excel**: Download list PR ke Excel
   - **Print List**: Print friendly version

5. **Row Actions** (per PR)
   - **View**: Lihat detail PR
   - **Edit**: Edit PR (hanya jika status = Draft dan user = creator)
   - **Delete**: Hapus PR (hanya jika Draft)
   - **Approve**: Shortcut approve (jika user = approver)
   - **Reject**: Shortcut reject
   - **Print**: Print PR document
   - **Convert to PO**: Langsung convert ke PO (jika Approved)
   - **Cancel**: Cancel PR

6. **Quick View Modal**
   - Popup untuk lihat info PR tanpa pindah halaman
   - Show: Header info, Items, Status
   - Button: Close, Go to Detail

7. **Sorting**
   - Klik column header untuk sort (ASC/DESC)
   - Default sort: PR Date descending (terbaru di atas)

8. **Pagination**
   - Dropdown: Show 10/25/50/100 per page
   - Navigation: First, Previous, Page Numbers, Next, Last
   - Total records info: "Showing 1-25 of 150 records"

#### Tabel Database yang Terlibat
- **`purchase_requests`**
  - Operasi: SELECT dengan filter dan pagination
  - Join dengan `users` untuk nama requester

- **`purchase_request_items`**
  - Operasi: SELECT COUNT untuk total items
  - Operasi: SELECT SUM untuk total amount

#### Query SQL Contoh
```sql
SELECT 
    pr.pr_id,
    pr.pr_number,
    pr.pr_date,
    pr.requester_id,
    u.user_name AS requester_name,
    pr.request_department_id,
    d.department_name,
    pr.status,
    pr.priority,
    pr.required_date,
    pr.total_estimated_amount,
    COUNT(pri.pr_item_id) AS total_items,
    SUM(CASE WHEN pri.quantity_outstanding > 0 THEN 1 ELSE 0 END) AS outstanding_items
FROM purchase_requests pr
LEFT JOIN users u ON pr.requester_id = u.user_id
LEFT JOIN departments d ON pr.request_department_id = d.department_id
LEFT JOIN purchase_request_items pri ON pr.pr_id = pri.pr_id
WHERE 
    pr.pr_date BETWEEN ? AND ?
    AND (? IS NULL OR pr.status = ?)
    AND (? IS NULL OR pr.requester_id = ?)
GROUP BY pr.pr_id
ORDER BY pr.pr_date DESC
LIMIT ? OFFSET ?
```

#### Validasi & Authorization
- User biasa hanya lihat PR milik sendiri
- Department Head lihat semua PR di departmentnya
- Procurement Team/Buyer lihat semua PR
- Manager/Admin lihat semua PR
- Filter by user permission

#### Business Rules
- PR dengan required date < hari ini highlight merah (overdue)
- PR dengan priority High/Critical tampilkan icon urgent
- PR Draft tidak muncul di view buyer (hanya requester)

---

### 8. Purchase Request - Create/Edit (Buat/Edit PR)
**Urutan**: #8  
**Prioritas**: ⭐ CRITICAL  
**Estimasi**: 5-6 hari

#### Penjelasan
Halaman untuk membuat PR baru atau mengedit PR yang masih berstatus Draft. User mengisi informasi header PR (siapa, kapan, untuk apa) dan menambahkan item-item yang dibutuhkan beserta quantity dan estimasi harga. Form ini adalah core dari proses purchasing.

#### Fungsi Utama
- Input permintaan pembelian barang
- Specify quantity dan estimasi harga
- Set prioritas dan tanggal dibutuhkan
- Attach dokumen pendukung
- Submit untuk approval

#### Fitur yang Harus Dibuat

**1. Form Header PR**
- **PR Number**: 
  - Display only (auto-generate saat save)
  - Jika edit, tampilkan nomor existing
  
- **PR Date**:
  - Date picker
  - Default: Hari ini
  - Bisa diganti (jika allow backdated = true)
  
- **Request Department**:
  - Dropdown list departments
  - Default: Department user login
  
- **Requester**:
  - Autocomplete user
  - Default: User login (bisa diganti jika punya permission)
  
- **PR Type**:
  - Dropdown: Standard, Urgent, Stock Replenishment, Project
  - Default: Standard
  
- **Priority**:
  - Dropdown: Low, Normal, High, Critical
  - Default: Normal
  - Icon urgent untuk High/Critical
  
- **Required Date**:
  - Date picker
  - Mandatory
  - Validation: Harus >= PR Date
  - Highlight jika < 7 hari dari sekarang
  
- **Warehouse**:
  - Dropdown gudang (hanya yang aktif)
  - Mandatory
  - Menentukan tujuan pengiriman barang

**2. Reference Information**
- **Purpose**:
  - Textarea
  - Mandatory
  - Max 500 karakter
  - Placeholder: "Jelaskan tujuan pembelian ini..."
  
- **Project Code**:
  - Input text
  - Mandatory jika PR Type = Project
  - Optional untuk yang lain
  
- **Cost Center Code**:
  - Dropdown cost centers
  - Optional tapi recommended
  - Untuk budget tracking

**3. Item Details Grid/Table**
Tabel editable untuk input items:

- **Kolom**:
  - No (auto numbering)
  - **Item Code**: Autocomplete
    - Ketik untuk search
    - Dropdown suggestion
    - Bisa scan barcode
  - **Item Name**: Auto-fill setelah pilih item (read-only)
  - **Description**: Editable, default dari item master
  - **Qty Requested**: Input number
  - **UoM**: Auto-fill dari item, bisa override
  - **Est. Unit Price**: Input currency
  - **Est. Total**: Auto-calculate (Qty × Price), read-only
  - **Specification**: Textarea untuk spec khusus
  - **Notes**: Textarea untuk catatan
  - **Actions**: Delete item (icon trash)

- **Buttons**:
  - **+ Add Item**: Buka dialog search item
  - **Import from Excel**: Upload Excel file dengan items
  - **Add from Template**: (optional) pilih dari saved template

- **Item Search Dialog**:
  - Search box
  - Filter by category
  - Tabel hasil search (checkbox multi-select)
  - Button: Add Selected Items
  - Items yang sudah ada di grid tidak muncul (no duplicate)

- **Auto-calculation**:
  - Est. Total per item = Qty × Unit Price
  - Update realtime saat user input

- **Validasi per item**:
  - Item Code wajib (harus valid item dari master)
  - Qty harus > 0
  - Est. Price boleh 0 (untuk internal estimate)
  - No duplicate items dalam satu PR

**4. Summary Panel** (Right sidebar atau bottom)
- **Total Items**: Count items (realtime)
- **Total Estimated Amount**: SUM(Est. Total) semua items
- **Currency**: IDR (dari setting)
- Panel ini sticky/fixed saat scroll

**5. Attachments Section**
- **Upload Multiple Files**:
  - Drag & drop area
  - Button: Browse Files
  - Support: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (max 5MB per file)
  - Preview thumbnail untuk images
  - List uploaded files dengan:
    - File name
    - File size
    - Preview button
    - Delete button

**6. Notes Section**
- **Internal Notes**:
  - Textarea
  - Untuk catatan internal, tidak perlu disampaikan ke supplier

**7. Action Buttons** (Bottom of form, always visible)
- **Save as Draft**:
  - Simpan PR dengan status = 'draft'
  - Bisa di-edit lagi nanti
  - Tidak trigger approval
  - Shortcut: Ctrl+S
  
- **Submit for Approval**:
  - Simpan dan ubah status = 'submitted'
  - Trigger notification ke approver
  - Tidak bisa di-edit lagi (hanya view)
  - Validation ketat sebelum submit
  
- **Cancel**:
  - Kembali ke PR List
  - Jika ada perubahan, muncul konfirmasi
  
- **Print Preview**:
  - Preview PDF PR document

**8. Auto-save Feature**
- Auto-save ke local storage setiap 30 detik
- Jika koneksi putus atau browser close, data tidak hilang
- Saat buka form lagi, muncul notif: "Found unsaved data, restore?"

**9. Copy from Existing PR** (Enhancement)
- Button: Copy from PR
- Pilih PR lama
- Copy semua items dan header info
- User bisa edit sebelum save

#### Tabel Database yang Terlibat
- **`purchase_requests`**
  - Operasi: INSERT (create), UPDATE (edit)
  - Auto-generate pr_number
  - Set created_by, created_at
  
- **`purchase_request_items`**
  - Operasi: INSERT, UPDATE, DELETE
  - Batch insert untuk multiple items
  - Delete items yang dihapus dari grid
  
- **`items`**
  - Operasi: SELECT (untuk autocomplete)
  
- **`warehouses`**
  - Operasi: SELECT (untuk dropdown)
  
- **`document_attachments`**
  - Operasi: INSERT (upload), DELETE (remove)
  - Link ke PR via document_type='PR', document_id=pr_id

#### Alur Proses Create PR
1. User klik "Create New PR"
2. Form muncul dengan default values
3. User isi header information
4. User add items satu per satu atau import
5. Sistem calculate total
6. User attach dokumen (optional)
7. User pilih aksi:

**Jika Save as Draft**:
```
1. Validate: minimal data (requester, warehouse, minimal 1 item)
2. Generate PR Number
3. INSERT to purchase_requests (status='draft')
4. INSERT items to purchase_request_items
5. Upload attachments
6. Show success message
7. Redirect to PR Detail atau List
```

**Jika Submit for Approval**:
```
1. Full validation (semua mandatory fields)
2. Generate PR Number (jika belum)
3. INSERT/UPDATE purchase_requests (status='submitted', submitted_at=now())
4. INSERT/UPDATE items
5. Upload attachments
6. Determine approver based on approval rules
7. Send notification ke approver (email/in-app)
8. Show success message
9. Redirect to PR Detail (read-only)
```

#### Alur Proses Edit PR
1. User klik "Edit" dari PR List atau Detail
2. Load existing PR data
3. Populate form dengan data PR
4. Load items ke grid
5. Load attachments
6. User bisa ubah data (kecuali PR Number)
7. Save changes:
   - UPDATE purchase_requests
   - UPDATE/INSERT/DELETE items (compare old vs new)
   - Handle attachment changes
8. Jika status berubah Draft → Submitted, trigger approval

#### Validasi Lengkap
**Header Validation**:
- PR Date wajib diisi
- Required Date wajib diisi dan >= PR Date
- Requester wajib diisi
- Warehouse wajib diisi
- Purpose wajib diisi (minimal 10 karakter)
- Project Code wajib jika PR Type = Project

**Items Validation**:
- Minimal harus ada 1 item
- Semua items harus punya Item Code yang valid
- Qty setiap item harus > 0
- No duplicate items (sama item code dalam 1 PR)
- Est. Unit Price boleh 0 tapi sebaiknya diisi

**Business Validation**:
- Jika Required Date < hari ini + 7 hari, muncul warning
- Jika Total Amount > threshold tertentu, perlu approval khusus

#### Authorization
- User hanya bisa edit PR milik sendiri (kecuali admin)
- Hanya PR dengan status Draft yang bisa di-edit
- PR Submitted/Approved tidak bisa di-edit (hanya view)

---

*Dokumen ini berlanjut dengan menu-menu berikutnya...*
*Untuk menghemat space, saya akan melanjutkan dengan ringkasan urutan yang tersisa*

---

## RINGKASAN URUTAN MENU (Lanjutan)

### FASE 2: PURCHASE REQUEST (Lanjutan)
9. **PR View Detail** - 2-3 hari
10. **PR Approval** - 4-5 hari
11. **PR Reports** - 3-4 hari

### FASE 3: PURCHASE ORDER (Minggu 8-11)
12. **PO List** - 3-4 hari
13. **PO Create/Edit** - 6-7 hari
14. **PO Convert from PR** - 5-6 hari
15. **PO View Detail** - 3-4 hari
16. **PO Approval** - 4-5 hari
17. **PO Reports** - 4-5 hari

### FASE 4: GOODS RECEIPT (Minggu 12-15)
18. **GR List** - 3-4 hari
19. **GR Create/Edit** - 6-7 hari
20. **GR View Detail** - 3-4 hari
21. **GR Inspection** - 5-6 hari
22. **GR Reports** - 3-4 hari

### FASE 5: DASHBOARD & ANALYTICS (Minggu 16-17)
23. **Purchasing Dashboard** - 5-6 hari

### FASE 6: ADVANCED SETTINGS (Minggu 18-19)
24. **Approval Workflow Configuration** - 4-5 hari
25. **Email Templates** - 3-4 hari

### FASE 7: OPTIONAL ADVANCED FEATURES (Minggu 20+)
26. **Supplier Portal** - 7-10 hari (Optional)
27. **Contract Management** - 6-8 hari (Optional)
28. **RFQ Module** - 8-10 hari (Optional)

---

## TOTAL ESTIMASI WAKTU

### MVP (Minimum Viable Product)
**Menu 1-23 (tanpa optional features)**
- **Durasi**: 16-17 minggu (4 bulan)
- **Team**: 2 developers (1 Backend, 1 Frontend)
- **Effort**: ~640-680 jam

### Full Featured (dengan Settings)
**Menu 1-25**
- **Durasi**: 18-19 minggu (4.5 bulan)
- **Team**: 2 developers
- **Effort**: ~720-760 jam

### With Advanced Features
**Menu 1-28 (semua fitur)**
- **Durasi**: 26-30 minggu (6.5-7.5 bulan)
- **Team**: 2-3 developers
- **Effort**: ~1040-1200 jam

---

## REKOMENDASI PRIORITAS

### CRITICAL (Harus ada di MVP)
1-10: Master Data + PR Module lengkap
12-16: PO Module core (tanpa reports dulu)
18-20: GR Module core (tanpa inspection detail dulu)

### HIGH (Phase 2, setelah MVP)
11, 17, 22: All Reports
14: PR to PO Conversion
21: GR Inspection
23: Dashboard

### MEDIUM (Phase 3, Enhancement)
24-25: Advanced Settings
Improvement & optimization

### LOW (Phase 4, Optional)
26-28: Advanced Features (jika ada budget & waktu)

---

**Version**: 1.0  
**Last Updated**: February 2026  
**Document Type**: Development Roadmap