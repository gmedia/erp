# Rancangan Fitur: Peminjaman Kendaraan Perusahaan

> **Status:** Draft reviewed by deep agent — koreksi BLOCKER/HIGH telah diintegrasikan.
> **Scope modul:** `vehicle-loans` (slug API & route FE)
> **Stack:** Laravel 12 JSON API + React 19 SPA (Sanctum), mengikuti `AGENTS.md`, `docs/development-patterns.md`, `docs/ui-design-rules.md`.
> **Prinsip multi-perusahaan:** tidak ada hardcode identitas/brand. Identitas diambil dari source of truth yang sudah ada (`Setting` + `/api/me` + `companies`/`company_id`), bukan env baru.

---

## 1. Ringkasan & Keputusan Arsitektur

### 1.1 Alur bisnis (happy path)

```text
[QR di loket/pos] → scan → /vehicle-loans/request (wajib login)
  → isi form permintaan → simpan DRAFT → kirim permintaan
  → ApprovalRequest (pipeline existing) → notifikasi ke approver
  → approver review + (opsional) ubah driver/unit → approve / reject(+alasan)
  → notifikasi ke peminjam (disetujui / ditolak + alasan)
  → [jika disetujui final] form pengambilan → upload bukti → ambil kunci di security
  → security verifikasi status approval (source of truth) → serah kunci
  → selesai pinjam → form pengembalian → upload bukti + kondisi + insiden
```

> **Catatan review:** notifikasi "disetujui" hanya dikirim pada **approval final**. Approve pada step awal dari flow multi-step tidak mengubah `vehicle_loans.status` menjadi `approved` (tetap `in_progress`).

### 1.2 Keputusan reuse (hasil audit codebase)

| Kebutuhan | Keputusan | Bukti di codebase |
|---|---|---|
| Approval multi-step, approve/reject + alasan, audit | **PAKAI ULANG** pipeline existing | `ApprovalRequest`, `ApprovalFlow`, `ApprovalFlowStep`, `ApprovalRequestStep`, `ApprovalAuditLog`, `TriggerApprovalAction`, `ApproveApprovalRequestAction`, `RejectApprovalRequestAction`, `MyApprovalController`, `routes/api/my-approvals.php`. `RejectApprovalRequestAction.php:23` menyimpan `comments` di step dengan `status='rejected'` → dipakai sebagai `rejection_reason`. `RejectMyApprovalRequest.php:20` mewajibkan `comments`. |
| Notifikasi in-app + multi-channel | **EXTEND** jadi modul generik | `User` sudah `Notifiable` (`app/Models/User.php:58`), tapi **belum ada** migrasi tabel `notifications`. Notifikasi existing hanya email: `ApprovalRequestCreated` → `SendApprovalRequestNotifications` → `ApprovalRequestNotification`. |
| Master driver | **BARU**, relasi ke `Employee` | `Employee::$fillable` = `employee_id,name,email,phone,user_id` (`app/Models/Employee.php:65-71`). **Department/branch TIDAK ada di Employee** — di-drop oleh `database/migrations/2026_07_10_000004_drop_employment_columns_from_employees_table.php:14-35`. Sumber benar: `Employment` (`app/Models/Employment.php:48-59`: `company_id, department_id, branch_id, is_current`) via `Employee::currentEmployment()` (`Employee.php:122-125`), pola `ResolvesBranchScope.php:84`. |
| Master unit kendaraan | **BARU**, relasi ke `Asset` + kategori | `Asset` punya `asset_category_id`, `branch_id`, `department_id`, `employee_id`, `status`, `condition` (`app/Models/Asset.php:124-152`). |
| Upload foto | **BARU dengan disk privat**, bukan pola `AssetMovementController` | `AssetMovementController::storeDocument()` memakai `Storage::disk('public')` (visibility public + symlink `/storage`, `config/filesystems.php:41-48`) — **tidak layak untuk SIM**. Lihat §9. |
| Draft | **PAKAI status di entity**, bukan tabel terpisah | Pola transaksi ERP memakai status di entity + soft-cancel. Pisahkan FormRequest draft (longgar) vs submit (penuh). |
| QR | **QR statis berisi URL**, generated client-side | `qrcode.react` sudah ada di `package.json`; tidak perlu Artisan QR command. |

**Kesimpulan:** approval **tidak dibuat ulang**. Cukup daftarkan `VehicleLoan` sebagai `approvable_type` **FQCN** `App\Models\VehicleLoan` dengan `flow_code = vehicle-loan.default`, lalu pakai endpoint `/my-approvals` yang ada. Layer approval generik tidak diubah; mutasi assignment (ganti driver/unit) jadi action khusus modul.

---

## 2. Konfigurasi & Anti-Hardcode (Multi-Perusahaan)

### 2.1 File config baru: `config/vehicle_loan.php`

> **Koreksi review (BLOCKER):** `company_name`/`company_logo` **tidak** ditaruh di config/env. Aplikasi sudah memakai `Setting::get('company_name')` / `Setting::get('company_logo_path')` (`app/Http/Controllers/Api/AuthController.php:158-159`, `app/Providers/AppServiceProvider.php:34-35`) dan mengekspos `companyName`/`companyLogoUrl` via `/api/me` (`AuthController.php:153-167`). Env `COMPANY_NAME`/`COMPANY_LOGO` **dihapus** — dua source of truth akan bertentangan dengan Admin Settings UI.

```php
return [
    // Base URL deep link email — konvensi existing (config/app.php:58).
    'public_base_url' => config('app.frontend_url', config('app.url')),

    // Path route form (QR di-generate dari konfigurasi ini)
    'qr_paths' => [
        'request'  => '/vehicle-loans/request',
        'pickup'   => '/vehicle-loans/pickup',
        'return'   => '/vehicle-loans/return',
        'security' => '/vehicle-loans/security',
    ],

    // TriggerApprovalAction::resolveFlow() mencocokkan getMorphClass()
    // (app/Actions/Approvals/TriggerApprovalAction.php:104-111); tanpa morph map = FQCN.
    'approval_flow_code' => env('VEHICLE_LOAN_APPROVAL_FLOW_CODE', 'vehicle-loan.default'),
    'approvable_type' => \App\Models\VehicleLoan::class,

    // Kategori asset kendaraan dipilih di UI master, bukan id hardcode.
    'vehicle_asset_category_ids' => array_filter(explode(',', (string) env('VEHICLE_ASSET_CATEGORY_IDS', ''))),

    // Upload foto — JANGAN 'public' untuk SIM.
    'photo_disk' => env('VEHICLE_LOAN_PHOTO_DISK', 'local'), // privat + signed URL
    'photo_max_kb' => (int) env('VEHICLE_LOAN_PHOTO_MAX_KB', 5120),
    // Samakan dengan AbstractAssetMovementRequest.php:18 (jpg,jpeg,png); webp perlu justifikasi.
    'photo_mimes' => ['jpg', 'jpeg', 'png'],
    'photo_retention_days' => (int) env('VEHICLE_LOAN_PHOTO_RETENTION_DAYS', 365),
    'draft_expiry_days' => (int) env('VEHICLE_LOAN_DRAFT_EXPIRY_DAYS', 7),

    'permissions' => [
        'view'      => 'vehicle_loan',
        'request'   => 'vehicle_loan.request',
        'pickup'    => 'vehicle_loan.pickup',
        'return'    => 'vehicle_loan.return',
        'security'  => 'vehicle_loan.security',
        'report'    => 'vehicle_loan.report',
        'master'    => 'vehicle_loan.master',
    ],
];
```

> **Tenant:** `companies` sudah ada (`database/migrations/2026_07_10_000000_create_companies_table.php`), `company_id` di `employments`/`branches` (`...000001_add_company_id_to_branches_table.php`). `branch_id` + `BranchResolverRegistry` (§9) cukup untuk scoping. Bila satu deployment melayani banyak company aktif, tambahkan `company_id` eksplisit ke `vehicle_loans`/`vehicle_drivers`/`vehicle_units` — jangan pakai env company name sebagai pengganti.

### 2.2 Aturan anti-hardcode (wajib dipatuhi agent)

1. Dilarang menulis nama perusahaan/brand di migration seeder, view email, label UI, notifikasi, README modul, atau komentar kode.
2. Identitas di UI/email memakai `companyName` dari `/api/me` (SPA) atau `Setting::get('company_name')` (backend/email) — **bukan** `__('vehicle_loan.company_name')` atau `config('vehicle_loan.company_name')` yang tidak ada. Bila ingin key translasi MH, daftarkan dulu di `lang/id.json`.
3. Logo memakai `companyLogoUrl` dari `/api/me` / `Setting::get('company_logo_path')`; fallback generik (inisial dari company name), bukan logo branded.
4. QR dibuat dari `config('vehicle_loan.public_base_url') . config('vehicle_loan.qr_paths.*')`; tidak ada URL produksi di kode.
5. Kategori kendaraan tidak diasumsikan `id = 1`; dipilih di UI master / config.
6. Perusahaan baru cukup: set data `companies` + `Setting` + seed permission/menu — tidak ada perubahan kode.

> `AGENTS.md` menyebut repo `gmedia/erp` sebagai konteks historis; itu bukan identitas aplikasi yang boleh bocor ke artifact modul.

---

## 3. Model Data & Migrasi

### 3.1 Tabel baru

#### `vehicle_drivers` (master driver)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `employee_id` | FK `employees`, unique | Nama/dept diambil dari `employee.currentEmployment` |
| `license_number`, `license_type`, `license_expiry` | nullable | Validasi SIM masih berlaku saat submit |
| `is_active` | boolean default true | |
| `timestamps` | | |

#### `vehicle_units` (master unit kendaraan)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `asset_id` | FK `assets`, unique | 1 unit = 1 asset |
| `plate_number` | string, unique | Nomor polisi |
| `vehicle_type` | enum `car`,`motorcycle` | Aturan foto & kapasitas |
| `capacity` | unsignedInteger, nullable | |
| `is_active` | boolean default true | |
| `timestamps` | | |

#### `vehicle_loans` (entity utama)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id`, `code` (unique) | | `VL-{YYYYMMDD}-{seq}` |
| `requester_id` | FK `users` | **Otomatis dari auth** |
| `department_id` | FK `departments`, nullable | **Otomatis dari** `user.employee.currentEmployment.department_id` (BUKAN `employee.department_id` yang sudah di-drop) |
| `branch_id` | FK `branches`, nullable | Dari `currentEmployment.branch_id`; wajib untuk `BranchResolverRegistry` |
| `company_id` | FK `companies`, nullable | Diisi dari `currentEmployment.company_id` untuk scoping tenant |
| `start_at`, `end_at` | datetime (UTC) | `end_at > start_at`, `start_at >= now()`; parse via `Carbon::parse()->utc()` di `prepareForValidation()` |
| `purpose` | text | `required|min:5|max:1000` |
| `driver_id` | FK `vehicle_drivers`, nullable | Boleh kosong → diisi approver |
| `vehicle_unit_id` | FK `vehicle_units`, nullable | Boleh kosong → diisi approver |
| `status` | string | `draft`, `pending`, `in_progress`, `approved`, `rejected`, `pickup_done`, `returned`, `cancelled`. Mirror dari ApprovalRequest, **bukan source of truth** |
| `rejection_reason` | text, nullable | Dari step `status='rejected'` → `comments` |
| `review_notes` | text, nullable | Catatan approver saat ubah driver/unit |
| `timestamps`, `deleted_at` | | Soft delete histori |

#### `vehicle_loan_pickups`
`id`, `vehicle_loan_id` (unique), `requester_id`, `photo_mode` (`without_driver`/`with_driver`), `selfie_photo_path/name`, `driver_snapshot_id`, `unit_snapshot_id`, `picked_at`, `key_handover_at`, `security_user_id`, `timestamps`.

#### `vehicle_loan_returns`
`id`, `pickup_id` (unique), `vehicle_loan_id`, `returner_id`, `handover_photo_path/name`, `condition_photo_path/name`, `incident` (bool), `incident_note` (wajib jika incident), `odometer_start/end` (opsional), `returned_at`, `received_by_security_id`, `timestamps`.

#### `notifications` (generik, Laravel database notifications)
`id`, `type`, `notifiable_type`, `notifiable_id`, `data` (json), **`dedupe_key` (string, unique)**, `read_at`, `timestamps`. Kolom `dedupe_key` wajib untuk idempotensi queue retry.

#### `notification_preferences` (opsional, disarankan masuk slice 3)
`id`, `user_id`, `channel` (`database`,`mail`,`whatsapp`), `event_key`, `enabled`, `timestamps`, unique `(user_id, channel, event_key)`.

### 3.2 Relasi & constraint

- `VehicleLoan` → `belongsTo(User/Department/Branch/VehicleDriver/VehicleUnit)`, `hasOne(Pickup)`, `hasOne(Return)`, `morphMany(ApprovalRequest,'approvable')`.
- `VehicleDriver` → `belongsTo(Employee)` → `currentEmployment` → `Department/Branch/Company`.
- `VehicleUnit` → `belongsTo(Asset)` → `AssetCategory`.
- `ApprovalRequest` sudah `morphTo('approvable')` → langsung resolve `VehicleLoan`. **Tidak ada kolom baru di tabel approval.**
- Index: `(status, submitted_at)`, `(requester_id,status)`, `(start_at,end_at)`; unique `asset_id`, `plate_number`, `employee_id`, pickup/return ke induk.
- FK `restrictOnDelete` untuk data histori; soft-delete aman.

### 3.3 Seeder approval flow (wajib)

`database/seeders/ApprovalFlowSampleDataSeeder.php:18-110` hanya membuat flow untuk Asset/PurchaseRequest/PurchaseOrder. Tambah seeder `vehicle-loan.default`: `approvable_type = \App\Models\VehicleLoan::class`, step `approver_type='user'` + `approver_user_id` — validasi `app/Http/Requests/ApprovalFlows/AbstractApprovalFlowRequest.php:24-27` **prohibisi** `approver_role_id`/`approver_department_id`, meski model mendukungnya. Branch/level flow bisa dipilih lewat `conditions` engine yang ada.

---

## 4. Modul Notifikasi Generik (Reusable)

### 4.1 Tujuan
Satu mekanisme untuk semua modul: **database (in-app)**, **email**, opsional webhook/WhatsApp. Tidak ada logika channel di modul bisnis.

### 4.2 Komponen

| Komponen | Lokasi | Peran |
|---|---|---|
| `App\Contracts\NotifiableEvent` | `app/Contracts/` | `eventKey()`, `audience(): Collection<User>`, `channels()`, `payload()`, `targetUrl()` |
| `App\Services\NotificationService` | `app/Services/` | Satu entry `send()`. **Idempotent**: `firstOrCreate(['dedupe_key' => hash(event_key+entity+recipient)])` sebelum queue; upsert atomik |
| `App\Notifications\GenericAppNotification` | `app/Notifications/` | `toArray()` (DB) + `toMail()` (Markdown generik), company name dari `Setting` |
| `App\Jobs\SendNotificationJob` | `app/Jobs/` | Queue per recipient, retry 3× (aman karena dedupe_key) |
| `App\Models\NotificationPreference` | `app/Models/` | Opt-out per event & channel |
| `routes/api/notifications.php` | | `GET /notifications`, `POST /notifications/{id}/read`, `POST /read-all`, `GET /unread-count` |
| `NotificationBell.tsx`, `pages/notifications/index.tsx` | FE | Bell + badge, filter unread/all, deep-link |

### 4.3 Event key registry — `config/notifications.php` (baru)

```php
return [
    'events' => [
        'approval.request.created'  => ['label' => 'Permintaan approval baru', 'channels' => ['database','mail']],
        'approval.request.approved' => ['label' => 'Permintaan disetujui',      'channels' => ['database','mail']],
        'approval.request.rejected' => ['label' => 'Permintaan ditolak',        'channels' => ['database','mail']],
        'vehicle_loan.pickup.due'   => ['label' => 'Jadwal ambil kendaraan',    'channels' => ['database']],
        'vehicle_loan.return.due'   => ['label' => 'Jadwal pengembalian',       'channels' => ['database']],
    ],
];
```

### 4.4 Integrasi approval existing (tanpa mengubah engine)

1. `ApprovalRequestCreated` → listener lama `SendApprovalRequestNotifications` di `app/Providers/EventServiceProvider.php:22-24` **diganti** oleh `DispatchGenericNotification` yang memanggil `NotificationService` event `approval.request.created`.
2. **Event baru** `ApprovalRequestApproved` / `ApprovalRequestRejected` (belum ada di `app/Events/`) → dispatch di `ApproveApprovalRequestAction` / `RejectApprovalRequestAction` **setelah** update status, pakai `afterCommit()` bila dispatch dari dalam DB transaksi; daftarkan di `EventServiceProvider::$listen`.
3. Vehicle loan mendengarkan kedua event untuk: mirror `approved_at`/`approved_by` (dari `completed_at` + step akhir `acted_by`) atau `rejection_reason`, lalu kirim notifikasi ke `requester` dengan `targetUrl = /vehicle-loans/{id}`. Listener ini hanya **orchestration tipis**, bukan logika channel.
4. `NotificationService` tidak tahu apa-apa soal kendaraan.

### 4.5 Deep link
Email memakai `config('app.frontend_url', config('app.url'))` — sama dengan `resources/views/emails/approval-request-notification.blade.php:11`. SPA login: `ProtectedRoute` existing memakai `state={{ from: location }}` (`resources/js/components/protected-route.tsx:25-27`), **bukan** query `?redirect=`. Notifikasi deep-link mengikuti pola `state.from` tersebut.

---

## 5. Detail Form & Validasi

Semua form: mobile-first (1 kolom, input ≥44px, tombol sticky bawah), shadcn + `useEntityForm` + Zod, `data-testid` stabil.

### 5.1 Form Permintaan — `/vehicle-loans/request`

| Field | Sumber | Validasi |
|---|---|---|
| Nama karyawan | Auth (`user.name`/`employee.name`) | readonly |
| Department | `user.employee.currentEmployment.department.name` | readonly, fallback `—`; **jangan** `employee.department_id` |
| Jam, hari, tanggal | `start_at`, `end_at` | `required`, `end_at > start_at`, `start_at >= now()`, parse UTC; hari/jam tampil otomatis |
| Tujuan | `purpose` | `required|string|min:5|max:1000` |
| Driver | `driver_id` | `nullable|exists:vehicle_drivers,id`; dropdown searchable + opsi eksplisit "Kosong — diisi approver" |
| Unit | `vehicle_unit_id` | `nullable|exists:vehicle_units,id`; sama |

**Tombol:**
- **Simpan Draft** → `POST /vehicle-loans` `status=draft` (FormRequest longgar). Draft kedaluwarsa `draft_expiry_days` (config) — tetap tersimpan, harus dikirim ulang.
- **Kirim Permintaan** → `POST /vehicle-loans/{id}/submit` (FormRequest penuh).

**Kritis:** submit **wajib menggagalkan transaksi** bila `TriggerApprovalAction::execute()` return `null` (flow tidak ada / tanpa step — `TriggerApprovalAction.php:35-50`); jangan set status `pending` secara unconditional. Cek bentrok jadwal dengan predicate `start_at < other.end_at AND end_at > other.start_at` + `lockForUpdate()` untuk status aktif (`draft`,`pending`,`in_progress`,`approved`,`pickup_done`). Cek `vehicle_drivers.license_expiry >= start_at`.

### 5.2 Review Approval — `/my-approvals` (existing, diperluas)

- Endpoint & UI existing dipakai (`resources/js/pages/my-approvals/index.tsx`). Index sudah eager-load `request.approvable` (`MyApprovalController.php:20-24`), tapi **belum ada** `GET /my-approvals/{approvalRequest}` — tambahkan endpoint detail untuk panel review.
- Saat `approvable_type = App\Models\VehicleLoan`, panel menampilkan data + snapshot driver/unit + form review: ganti driver, ganti unit, catatan → `POST /vehicle-loans/{loan}/review-assignment`.
- `ReviewVehicleLoanAssignmentAction` wajib:
  - pakai guard yang **sama persis** dengan `findCurrentPendingAssignedStep()` (`MyApprovalController.php:91-105`), ditempatkan di namespace `my-approvals` atau duplikat guard-nya;
  - cek `approval_request.status in (pending,in_progress)` + `lockForUpdate()`; **tolak** setelah final approve / setelah step berpindah (race condition);
  - update `driver_id`/`vehicle_unit_id` + `review_notes`;
  - audit: `assignment_changed`. **BLOCKER:** `ApprovalAuditLog.event` adalah enum tertutup (`database/migrations/2026_03_01_134127_create_approval_audit_logs_table.php:15-19`: `submitted, step_approved, step_rejected, step_skipped, auto_approved, escalated, delegated, cancelled, resubmitted, completed`) → **tambah migrasi enum** ATAU pakai tabel `vehicle_loan_activity_logs` terpisah.
  - Catatan: mengganti assignment dapat mengubah hasil `conditions` flow yang dievaluasi saat submit — validasi ulang bila condition bergantung pada driver/unit.
- Tombol **Setujui**/**Tolak** tetap `/my-approvals/{approvalRequest}/approve|reject`. Tolak wajib `comments` → `rejection_reason` (dari step `rejected`). Flag `can_reject` pada flow step (`AbstractApprovalFlowRequest.php:32`) harus dipatuhi: bila false, tombol Tolak disembunyikan & backend menolak.
- Approve multi-step: `vehicle_loans.status` tetap `in_progress` sampai final.

### 5.3 Form Pengambilan — `/vehicle-loans/pickup`

| Field | Validasi | Catatan |
|---|---|---|
| Pilih permintaan | `required|exists:vehicle_loans,id` | Hanya loan milik user + `status=approved` (final) + belum ada pickup |
| Detail otomatis | readonly | Nama, dept, waktu, tujuan, driver, unit, plat |
| `photo_mode` | `required|in:without_driver,with_driver` | Auto dari `driver_id`, bisa dikonfirmasi |
| Foto | `required|file|mimes:jpg,jpeg,png|max:5120` | `without_driver` → selfie + SIM terlihat; `with_driver` → selfie bersama driver |

Submit → `POST /vehicle-loans/pickups` → `status=pickup_done`, notif security `vehicle_loan.pickup.due`.

> **Privasi (koreksi HIGH):** foto **jangan** ke disk `public`. Pakai disk privat (`config('vehicle_loan.photo_disk')=local`) + controller signed URL `GET /vehicle-loans/photos/{id}` dengan `temporaryUrl()`, autorisasi owner/approver/security, audit setiap view, retention `photo_retention_days`. Tidak ada penggunaan `temporaryUrl` di codebase saat ini → ini kode baru. `webp` tidak dipakai agar konsisten dengan `AbstractAssetMovementRequest.php:18`.

### 5.4 Form Pengembalian — `/vehicle-loans/return`

| Field | Validasi |
|---|---|
| Pilih pengambilan | `required|exists:vehicle_loan_pickups,id` (milik user, belum ada return) |
| Detail otomatis | readonly dari pickup + loan |
| Foto bukti serah terima | `required|file|mimes:jpg,jpeg,png|max:5120` |
| Foto kondisi kendaraan | `required|file|...` |
| Insiden | `required|boolean` |
| Catatan insiden | `required_if:incident,true` |
| Odometer | `nullable|integer|gte:odometer_start` (opsional) |

Submit → `POST /vehicle-loans/returns` → `status=returned`, notif approver & security, unit tersedia lagi. Idempotensi via unique `pickup_id` + cek status (409 bila duplikat).

### 5.5 Daftar Security — `/vehicle-loans/security`

- Permission `vehicle_loan.security`; scope branch via `ResolvesBranchScope`.
- Tabel/card mobile: kode, peminjam, dept, driver, unit, rentang, **badge status approval** (source of truth = `ApprovalRequest.status`: Disetujui hijau / Menunggu amber / Ditolak merah / Draft abu), status pickup/return.
- **Tombol "Serahkan Kunci" aktif hanya jika** pengecekan **server** terhadap `ApprovalRequest.status='approved'` (bukan hanya mirror `vehicle_loans.status`) **dan** pickup valid **dan** belum di-handover → `POST /vehicle-loans/pickups/{pickup}/handover`.
- Security **tidak bisa approve/reject**; boundary jelas.

---

## 6. API & Route (ringkas)

File baru: `routes/api/vehicle-loans.php`, `vehicle-drivers.php`, `vehicle-units.php`, `notifications.php`. Semua di `middleware('auth:sanctum')`; permission via `permission:{slug},true` (konvensi `routes/api/branches.php:6`). Business logic di Action, bukan controller.

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET/POST` | `/vehicle-loans` | Index filter / create draft |
| `GET/PATCH/DELETE` | `/vehicle-loans/{loan}` | Detail / update draft / cancel |
| `POST` | `/vehicle-loans/{loan}/submit` | Trigger approval (transaksi aman) |
| `POST` | `/vehicle-loans/{loan}/review-assignment` | Approver ubah driver/unit |
| `POST` | `/vehicle-loans/{loan}/pickups` | Submit pengambilan |
| `POST` | `/vehicle-loans/pickups/{pickup}/handover` | Security serah kunci |
| `POST` | `/vehicle-loans/returns` | Submit pengembalian |
| `GET` | `/vehicle-loans/my`, `/security-queue`, `/report` | Riwayat / security / dashboard |
| `GET` | `/vehicle-loans/photos/{id}` | Signed URL foto privat |
| CRUD | `/vehicle-drivers`, `/vehicle-units` | Master (`vehicle_loan.master`) |
| `GET/POST` | `/notifications`, `/notifications/{id}/read`, `/read-all` | Inbox notifikasi |

---

## 7. Dashboard & Report

`resources/js/pages/vehicle-loans/dashboard.tsx` memakai `ReportDataTablePage` + kartu ringkasan.

**Metrik:** total request per status; utilisasi unit (jam terpakai/tersedia); rata-rata durasi approval (`submitted_at` → `completed_at`); keterlambatan handover/return; insiden per periode; **overdue** (`end_at` terlewat & belum return).

**Filter:** tanggal, department, branch, company, vehicle_type, status, driver. **Export:** `VehicleLoanReportExport` via `AbstractReportIndexExport`. **Drill-down:** `/vehicle-loans/{id}` — detail + timeline approval + audit + foto (signed).

---

## 8. Frontend, QR & Mobile

### 8.1 QR
QR statis dicetak di pos/security, berisi URL dari `config('vehicle_loan.public_base_url') . qr_paths.*`. Render client-side dengan `qrcode.react` (sudah terpasang). QR **tidak** mengandung token/data request. Fallback: input kode manual. Bila base URL berubah, cetak ulang QR dari config.

### 8.2 Mobile
Form 1 kolom, `datetime-local` native, area sentuh ≥44px, tombol sticky; dropdown searchable dengan opsi "Kosong — diisi approver"; tabel → card di `< md`; upload `accept="image/*"` + preview + validasi server; bell notifikasi di header.

### 8.3 Route SPA (`app-routes.tsx`, lazy-loaded)
```
/vehicle-loans/request | /pickup | /return | /security | /dashboard | /:id
/notifications
```
`ProtectedRoute` existing hanya menerima `children` (`resources/js/components/protected-route.tsx:5-7`) — **jangan** pakai prop `permission`. Gunakan middleware backend `permission:` + gating FE via `useAuth()`. Login redirect memakai `state.from`.

---

## 9. Keamanan, Privasi & Audit

| Risiko | Mitigasi |
|---|---|
| Foto SIM terbaca publik | Disk privat + signed `temporaryUrl`, controller autorisasi owner/approver/security, audit view, retention config. **Tidak pakai disk `public`** (`config/filesystems.php:41-48`). |
| Branch/company leakage | Daftarkan `\App\Models\VehicleLoan::class => Direct` di `BranchResolverRegistry::STRATEGIES` (`app/Domain/Branch/BranchResolverRegistry.php:30-45`), else `approval_requests.branch_id` null (`TriggerApprovalAction.php:53-55`). Scope semua query dengan `ResolvesBranchScope.php:84-102` + `company_id`. |
| Security kasih kunci tanpa approval | Cek server langsung ke `ApprovalRequest.status='approved'`, bukan mirror. |
| Race review-assignment | `lockForUpdate()`, cek status request, tolak pasca-final approve, audit. |
| Submit approval gagal | Transaksi dibatalkan bila `TriggerApprovalAction` return null. |
| Notifikasi dobel | `dedupe_key` unique + `firstOrCreate` sebelum queue retry 3×. |
| Status mirror drift | ApprovalRequest = source of truth; mirror hanya untuk filter dashboard. |
| Upload dobel | Unique constraint + 409. |
| Waktu client | `Carbon::parse()->utc()` di `prepareForValidation`. |
| Brand bocor | `Setting`/`/api/me`, aturan §2.2. |

Audit: `ApprovalAuditLog` (event enum — tambah migrasi bila perlu `assignment_changed`) atau tabel `vehicle_loan_activity_logs` untuk pickup/handover/return.

---

## 10. Testing & Quality Gate

- **Pest** group `vehicle-loans`, `notifications`: draft→submit→approve; reject+alasan; review assignment (termasuk race & enum audit); pickup; handover (tolak bila belum approve); return; bentrok jadwal; license expired; permission; branch scope; idempotensi notifikasi; approval null rollback.
- **Unit**: Zod schemas, `NotificationService` dedupe, resolver `currentEmployment`.
- **E2E Playwright**: mobilescan→login→request→approval tab lain→pickup→security→return, `waitForResponse('/api/...')`.
- **Quality**: `sail test --group vehicle-loans`, `sail bin duster fix`, `npm run types`, `sail npm run lint`, Sonar gate sesuai `AGENTS.md`. `sail bin duster fix` wajib setelah perubahan PHP (import short-name, empty class comment).

---

## 11. Rencana Implementasi (8 slice, satu MR per slice)

1. Config + migrasi (termasuk `notifications.dedupe_key`, enum audit / activity log) + model + factory + seeder permission/menu + seeder flow + BranchResolver registration.
2. Master driver & unit + validasi bentrok & SIM expired.
3. Modul notifikasi generik (service, job, preference, endpoint, bell) + replace listener `EventServiceProvider`.
4. Entity `VehicleLoan`: draft + submit (rollback-safe) + trigger approval + event mirror.
5. Detail `/my-approvals`, review assignment (locking + audit), approve/reject + alasan.
6. Pickup + signed photo + handover security + return (insiden, odometer).
7. QR statis + halaman mobile + route SPA + permission guard.
8. Dashboard/report/export + E2E + `docs/user-guide-vehicle-loans.md` (akses via `/api/user-guide`).

---

## 12. Saran Perbaikan (Usulan ke User)

1. **Snapshot driver/unit** saat approval & pickup (`review_notes`, `*_snapshot_id`) — sudah masuk rancangan; cegah perubahan master merusak riwayat.
2. **Odometer opsional** di return — murah, membantu audit BBM & insiden.
3. **SIM kedaluwarsa** dicek saat submit (`license_expiry`).
4. **NotificationPreference** masuk slice 3 agar tidak spam & dipakai modul lain.
5. **Condition-based flow** engine existing dipakai untuk approver per cabang/departemen.
6. **Auto-reminder & status overdue** saat `end_at` terlewat belum return (event `vehicle_loan.return.due`).
7. **QR + kode manual** — QR bisa rusak; selalu sediakan input kode.
8. **Tabel `companies` / `company_id`** — rancangan memakai `company_id` via `Employment`/`Branch`, bukan env; ini yang benar untuk ERP yang dijual ke banyak perusahaan.

---

## 13. Ringkasan Review Deep Agent & Status

**Koreksi BLOCKER/HIGH yang sudah diintegrasikan:**
1. `Employee.department_id/branch_id` sudah di-drop → pakai `currentEmployment` (§1.2, §3.1, §5.1).
2. Identitas company sudah DB-backed (`Setting`/`/api/me`) → env company dihapus (§2.1–2.2, §12.8).
3. Event approve/reject baru + `EventServiceProvider` + audit enum (§4.4, §5.2).
4. `notifications.dedupe_key` unique + `firstOrCreate` (§3.1, §4.2).
5. Foto privat + signed URL (bukan disk `public`) (§2.1, §5.3, §9).
6. `BranchResolverRegistry` + `ResolvesBranchScope` + `company_id` (§9).
7. Locking review-assignment + submit rollback bila approval null + `can_reject` (§5.1–5.2).

**Koreksi MEDIUM juga dimasukkan:** FQCN `approvable_type`, endpoint detail approval, `ProtectedRoute` tanpa prop `permission` & redirect `state.from`, `frontend_url`, `draft_expiry_days`, `photo_retention_days`, validasi mime, QR client-side `qrcode.react`.

- [x] Rancangan awal dibuat
- [x] Review agent deep selesai → temuan diintegrasikan
- [x] Implementasi slice 1–8 (fondasi, notifikasi, master, alur penuh, FE, report/export, guide)
- [x] Mirror status approval → `vehicle_loans` (`MirrorVehicleLoanApprovalStatus`) + test
- [x] Export report XLSX/CSV (`ExportVehicleLoansAction`, `VehicleLoanExport`)
- [ ] Final disetujui untuk implementasi / merge MR
