---
name: Refactor Fullstack
description: Panduan refactor terkontrol untuk Laravel Fullstack
---
# PANDUAN REFACTOR TERKONTROL - LARAVEL FULL STACK

## 🎯 TUJUAN UTAMA

Lakukan **ANALISA dan REFACTOR** kode secara **TERKONTROL dan TERSTRUKTUR** pada modul berikut, **TANPA merusak**:

- API contract
- Frontend behavior
- Test yang sudah ada

**Refactor HARUS fokus pada:**

- Keterbacaan kode
- Konsistensi arsitektur
- Pemisahan tanggung jawab tanpa mengubah perilaku sistem

> **Prinsip Utama:** Refactor ≠ Rewrite

---

## 📦 SCOPE REFACTOR

### Backend & Frontend Modules

**Modul Departments:**

- `@/resources/js/pages/departments`
- `@/resources/js/components/departments`
- `@/app/Http/Actions/Departments`
- `@/app/Http/Domain/Departments`
- `@/app/Http/DTOs/Departments`
- `@/app/Http/Exports/Departments`
- `@/app/Http/Controllers/DepartmentController.php`
- `@/app/Http/Requests/Departments`
- `@/app/Http/Resources/Departments`
- `@/tests/e2e/departments`
- `@/tests/Feature/DepartmentControllerTest.php`
- `@/tests/Unit/DepartmentTest.php`
- `@/tests/Actions/Departments`
- `@/tests/Domain/Departments`
- `@/tests/Requests/Departments`
- `@/tests/Resources/Departments`

**Modul Positions:**

- `@/resources/js/pages/positions`
- `@/resources/js/components/positions`
- `@/app/Http/Actions/Positions`
- `@/app/Http/Domain/Positions`
- `@/app/Http/DTOs/Positions`
- `@/app/Http/Exports/Positions`
- `@/app/Http/Controllers/PositionController.php`
- `@/app/Http/Requests/Positions`
- `@/app/Http/Resources/Positions`
- `@/tests/e2e/positions`
- `@/tests/Feature/PositionControllerTest.php`
- `@/tests/Unit/PositionTest.php`
- `@/tests/Actions/Positions`
- `@/tests/Domain/Positions`
- `@/tests/Requests/Positions`
- `@/tests/Resources/Positions`

**Modul Employees:**

- `@/resources/js/pages/employees`
- `@/resources/js/components/employees`
- `@/app/Http/Actions/Employees`
- `@/app/Http/Domain/Employees`
- `@/app/Http/DTOs/Employees`
- `@/app/Http/Exports/Employees`
- `@/app/Http/Controllers/EmployeeController.php`
- `@/app/Http/Requests/Employees`
- `@/app/Http/Resources/Employees`
- `@/tests/e2e/employees`
- `@/tests/Feature/EmployeeControllerTest.php`
- `@/tests/Unit/EmployeeTest.php`
- `@/tests/Actions/Employees`
- `@/tests/Domain/Employees`
- `@/tests/Requests/Employees`
- `@/tests/Resources/Employees`

### Shared Code

- `@/resources/js/components/common`
- `@/routes/web.php`
- `@/app/Models`

---

## 🚫 ATURAN PALING PENTING: API & BEHAVIOR COMPATIBILITY

### DILARANG KERAS Mengubah:

- ❌ Route URI
- ❌ HTTP method
- ❌ Request payload structure
- ❌ Response JSON keys
- ❌ Response data structure
- ❌ Query parameter names yang sudah digunakan frontend
- ❌ Frontend data-testid atau selector untuk e2e test

### Yang DIPERBOLEHKAN:

- ✅ Refactor internal (pemindahan logic antar layer)
- ✅ Perbaikan struktur kode
- ✅ Penambahan typing, Resource, FormRequest
- ✅ Pemindahan logic ke layer yang tepat
- ✅ Optimisasi tanpa mengubah output

### Kompatibilitas Wajib:

- Backend HARUS tetap kompatibel dengan frontend existing
- Frontend HARUS tetap kompatibel dengan backend existing

---

## 🏗️ ARSITEKTUR BACKEND (WAJIB DIPATUHI)

### 1. Controller (Facade / Transport Layer)

**Tanggung Jawab:**

- Controller HARUS **tipis**
- Controller adalah **facade layer** yang menghubungkan HTTP dengan aplikasi

**Controller HANYA BOLEH:**

- Menerima FormRequest (validation)
- Mapping data ke DTO (jika diperlukan)
- Memanggil Action
- Mengembalikan Resource atau JsonResponse

**Controller TIDAK BOLEH:**

- ❌ Berisi business rule
- ❌ Berisi query kompleks
- ❌ Berisi perhitungan domain
- ❌ Mengakses model secara langsung untuk logic kompleks

**Struktur Wajib:**

- Extends `Controller`
- Implementasi method HARUS eksplisit (tidak mengandalkan inherited CRUD)
- Method signature HARUS eksplisit dan konsisten

---

### 2. FormRequest (Validation Layer)

**Aturan Wajib:**

- GUNAKAN FormRequest **spesifik** untuk setiap operasi
- JANGAN gunakan `Illuminate\Http\Request` untuk method yang butuh validasi

**Penamaan:**

- `StoreDepartmentRequest`
- `UpdateEmployeeRequest`
- `ExportPositionRequest`

**Setiap FormRequest WAJIB memiliki:**

- `rules()` lengkap sesuai database schema
- `authorize()` yang jelas
- Custom validation messages (jika relevan)

**Validation Rules:**

- Rules HARUS sesuai database schema
- Unique constraint: handle update dengan `ignore` current model
- Gunakan: `exists`, `unique`, `nullable`, `sometimes` secara tepat
- JANGAN membuat rules spekulatif

---

### 3. DTO (Data Transfer Object)

**Kapan HARUS Menggunakan DTO:**

- Data berasal dari HTTP dan dikonsumsi oleh Action
- Data melewati lebih dari satu layer
- Terdapat **≥ 3 field bermakna bisnis**

**Kapan TIDAK Perlu DTO:**

- Logic hanya CRUD sederhana (1-2 field)
- Tidak ada orchestration
- Data langsung disimpan tanpa transformasi

**Aturan DTO:**

- WAJIB immutable (`readonly` jika PHP 8.1+)
- WAJIB memiliki typed properties
- Dibuat via named constructor: `fromRequest()` / `fromArray()`

**DTO TIDAK BOLEH:**

- ❌ Mengandung business logic
- ❌ Mengakses database
- ❌ Mengakses HTTP (Request/Response)

**Contoh Penamaan:**

- `StoreDepartmentData`
- `UpdateEmployeeData`
- `ExportPositionData`

---

### 4. Action (Application Service / Use Case)

**Definisi:**

- Setiap Action merepresentasikan **SATU use-case bisnis**
- Action adalah **orchestrator** yang mengoordinasikan alur kerja

**Penamaan:**

- Format: **Verb + Noun + Action**
- Contoh: `CreateDepartmentAction`, `UpdateEmployeeAction`, `ExportPositionsAction`

**Action Bertanggung Jawab Atas:**

- Orchestration (koordinasi berbagai service/model)
- DB Transaction management
- Pemanggilan Domain Service
- Dispatch Event (jika ada)

**Action TIDAK BOLEH:**

- ❌ Menyimpan aturan bisnis inti (pindahkan ke Domain Service)
- ❌ Berisi validasi HTTP (gunakan FormRequest)
- ❌ Mengetahui detail transport (HTTP headers, status code, etc)

**Kapan TIDAK Perlu Action:**

- Logic hanya CRUD sederhana tanpa orchestration
- Tidak ada business rule kompleks
- Tidak ada transaction multi-tabel

---

### 5. Domain Service (Business Rules)

**Kapan HARUS Membuat Domain Service:**

- Ada **aturan bisnis murni** yang kompleks
- Ada **invariant domain** yang perlu dijaga
- Ada **policy atau perhitungan bisnis** yang bisa reusable

**Kapan TIDAK Perlu Domain Service:**

- Hanya validasi sederhana (cukup di FormRequest)
- Hanya CRUD (cukup di Model atau Action)
- Logic terlalu spesifik untuk satu use-case saja

**Karakteristik Domain Service:**

- Bebas dari HTTP (Request/Response)
- Bebas dari Resource
- Bebas dari query database langsung
- Dapat dipakai oleh **lebih dari satu Action**
- Boleh berubah **tanpa mengubah workflow Action**

**Contoh Penamaan:**

- `DepartmentPolicy`
- `EmployeeSalaryCalculator`
- `PositionHierarchyValidator`

---

### 6. Repository Pattern

**Aturan:**

- Repository **TIDAK WAJIB** digunakan
- Repository **HANYA** untuk abstraksi query kompleks

**Kapan Menggunakan Repository:**

- Query sangat kompleks dengan banyak join
- Ada kebutuhan **abstraction nyata** (misalnya multiple DB support)
- Query perlu di-reuse di banyak tempat

**Kapan TIDAK Perlu Repository:**

- Logic hanya CRUD sederhana
- Query hanya `find()`, `create()`, `update()`
- Eloquent sudah cukup ekspresif

**Repository TIDAK BOLEH:**

- ❌ Mengandung business rule

**Jika Repository Tidak Digunakan:**

- Gunakan Eloquent **langsung di Action**
- WAJIB jelaskan alasannya secara eksplisit

---

### 7. Event & Listener

**Event Digunakan Untuk:**

- Side effects (notifikasi, logging, etc)
- Proses async (queue)
- Proses lintas bounded context

**Event TIDAK BOLEH:**

- ❌ Menggantikan domain logic
- ❌ Mengandung aturan bisnis inti

---

### 8. 🚨 PRINSIP ANTI OVER-ENGINEERING (WAJIB)

**JANGAN Membuat DTO / Action / Domain Service Jika:**

- Logic hanya CRUD sederhana
- Tidak ada orchestration
- Tidak ada aturan bisnis nyata
- Hanya save model tanpa perhitungan

**Dalam Kasus Tersebut:**

- Controller tetap tipis
- Logic boleh langsung di Controller atau Model
- Eloquent methods (`create()`, `update()`) sudah cukup

**WAJIB:**

- Jika layer sengaja TIDAK dibuat, **jelaskan alasannya secara eksplisit**

---

## 💻 ATURAN CODING BACKEND (LARAVEL)

### Controller Method Signatures

**Signature HARUS Eksplisit:**

```php
// ✅ BENAR
public function index(Request $request): JsonResponse
public function store(StoreDepartmentRequest $request): JsonResponse
public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
public function destroy(Department $department): JsonResponse

// ❌ SALAH
public function store(Request $request) // tidak spesifik, no return type
```

**Parameter Pertama HARUS FormRequest untuk:**

- `store()`
- `update()`
- `export()`
- Method lain yang butuh validasi kompleks

---

### Response & Resource Classes

**JANGAN Return:**

- ❌ Array mentah: `return ['data' => $departments];`
- ❌ JSON mentah: `return response()->json($data);`

**GUNAKAN Resource Spesifik:**

- Single item → `DepartmentResource`
- List/Collection → `DepartmentCollection`

**Status Code Konsisten:**

- `store()` → **HTTP 201** Created
- `update()` → **HTTP 200** OK
- `destroy()` → **HTTP 204** No Content atau **200** dengan message
- `export()` → **HTTP 200** dengan struktur:
    ```json
    {
        "url": "storage/exports/...",
        "filename": "export_2025_01_01.xlsx"
    }
    ```

---

### Index Method (Pagination, Search, Sort)

**Index Method WAJIB Mendukung:**

- Pagination (Laravel paginator)
- Search (multi-column jika sudah ada sebelumnya)
- Sorting (default sorting jika tidak diberikan)

**Query Parameter:**

- JANGAN mengubah nama parameter yang sudah digunakan frontend
- Contoh existing: `?search=...&sort_by=...&sort_order=...`

**Search Rules:**

- HARUS aman (no raw SQL, gunakan `where()` atau `whereRaw()` dengan binding)
- Boleh multi-column jika sebelumnya sudah ada

**Pagination:**

- Gunakan `paginate()` atau `simplePaginate()`
- Response HARUS menyertakan metadata pagination

---

### Database & Transaction

**Gunakan DB Transaction Untuk:**

- Store/update yang menyentuh **lebih dari 1 tabel**
- Operasi dengan relasi kompleks
- Operasi yang harus atomic

```php
DB::transaction(function () use ($data) {
    $department = Department::create($data);
    $department->positions()->sync($positionIds);
});
```

**Pastikan Rollback pada Error:**

- Laravel otomatis rollback jika exception terjadi dalam transaction

---

### Error Handling

**Gunakan Standar Laravel:**

- **422** → Validation error (otomatis dari FormRequest)
- **404** → Model not found (otomatis dari route model binding)
- **409** → Conflict (jika relevan, misalnya duplicate)
- **500** → Server error (otomatis dari exception handler)

**JANGAN:**

- ❌ Membuat format error response baru
- ❌ Override exception handler tanpa alasan kuat

**Gunakan Exception Bawaan:**

- `ModelNotFoundException`
- `ValidationException`
- `AuthorizationException`

---

### Resource Classes

**`toArray()` Method HARUS:**

- Konsisten dengan response lama
- Menyertakan **seluruh field** yang dibutuhkan frontend
- Menyertakan timestamps (`created_at`, `updated_at`) jika sebelumnya ada
- Menyertakan relasi jika sebelumnya ada

**JANGAN:**

- ❌ Mengubah nama key response
- ❌ Menghapus field yang digunakan frontend

**Contoh:**

```php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'description' => $this->description,
        'created_at' => $this->created_at?->toISOString(),
        'updated_at' => $this->updated_at?->toISOString(),
        'positions_count' => $this->when($this->relationLoaded('positions'),
            fn() => $this->positions->count()
        ),
    ];
}
```

---

### PHPDoc (WAJIB)

**Setiap Controller Method WAJIB Memiliki PHPDoc Lengkap:**

- Deskripsi singkat
- `@param` dengan fully qualified class name
- `@return` dengan fully qualified class name

**Contoh:**

```php
/**
 * Store a newly created department.
 *
 * @param \App\Http\Requests\StoreDepartmentRequest $request
 * @return \Illuminate\Http\JsonResponse
 */
public function store(StoreDepartmentRequest $request): JsonResponse
{
    // implementation
}
```

**Format PHPDoc:**

```php
/**
 * [Deskripsi singkat operasi]
 *
 * @param \Full\Qualified\ClassName $paramName
 * @return \Full\Qualified\ReturnType
 */
```

---

## 🎨 ATURAN CODING FRONTEND

### Yang DILARANG Diubah:

- ❌ Struktur data yang diterima dari API
- ❌ `data-testid` (digunakan untuk e2e test)
- ❌ Kontrak event antar komponen
- ❌ Struktur payload API request
- ❌ Key response API
- ❌ Selector yang digunakan e2e test

### Fokus Frontend Refactor:

- ✅ Keterbacaan kode
- ✅ Konsistensi struktur component
- ✅ Reuse component (mengurangi duplikasi)
- ✅ Typing (TypeScript)
- ✅ Konsistensi naming convention

---

## 🧪 ATURAN TESTING & BUILD

### Command yang HARUS PASS:

```bash
./vendor/bin/sail test
./vendor/bin/sail npm run test:e2e
```

### Jika Test Gagal:

- ✅ WAJIB diperbaiki
- ❌ DILARANG menghapus test
- ❌ DILARANG menonaktifkan assertion
- ❌ DILARANG skip test

---

## ⚙️ ATURAN COMMAND TERMINAL

### SELALU Gunakan Sail:

```bash
./vendor/bin/sail <command>
```

### JANGAN Mengeksekusi:

```bash
./vendor/bin/sail npm run format
```

### WAJIB Urutan Build:

```bash
./vendor/bin/sail npm run build
./vendor/bin/sail npm run types
```

---

## 📋 OUTPUT YANG DIHARAPKAN

### 1. Kode Hasil Refactor

- Behavior tetap sama (no breaking changes)
- Struktur lebih rapi dan konsisten
- Arsitektur lebih jelas
- Lebih mudah di-maintain

### 2. Dokumentasi Singkat (Bullet Points)

Untuk setiap modul, jelaskan:

- **Layer apa saja yang dibuat:**
    - FormRequest: `StoreDepartmentRequest`, `UpdateDepartmentRequest`
    - DTO: `StoreDepartmentData` (jika digunakan)
    - Action: `CreateDepartmentAction` (jika digunakan)
    - Domain Service: `DepartmentPolicy` (jika digunakan)
    - Resource: `DepartmentResource`, `DepartmentCollection`

- **Alasan mengapa layer digunakan atau TIDAK digunakan:**
    - Contoh: "Action tidak dibuat karena logic hanya CRUD sederhana tanpa orchestration"
    - Contoh: "Domain Service dibuat karena ada aturan bisnis kompleks untuk validasi hierarki posisi"

### 3. Controller yang:

- Tipis (facade only)
- Konsisten dengan signature yang jelas
- Mudah dibaca dan dipahami
- PHPDoc lengkap

---

## ✅ PRINSIP AKHIR

### Decision Making:

- **Jika ragu:** PERTAHANKAN implementasi lama
- **Jangan mengorbankan:** Stabilitas demi "kebersihan"
- **Fokus pada:** Kejelasan dan keberlanjutan jangka panjang

### Priority:

1. **Stabilitas** (no breaking changes)
2. **Konsistensi** (follow Laravel best practices)
3. **Keterbacaan** (code clarity)
4. **Maintainability** (easy to extend)

### Red Flags (Tanda Bahaya):

- 🚩 Test yang gagal setelah refactor
- 🚩 Frontend error setelah refactor backend
- 🚩 Response API berubah struktur
- 🚩 Over-engineering (layer yang tidak perlu)

---

## 📚 CHECKLIST REFACTOR

### Per Modul (Departments / Positions / Employees):

**Backend:**

- [ ] Controller: tipis, signature jelas, PHPDoc lengkap
- [ ] FormRequest: spesifik untuk setiap operasi, rules lengkap
- [ ] Resource: konsisten dengan response lama, semua field ada
- [ ] DTO: dibuat jika memenuhi kriteria (≥3 field, multi-layer)
- [ ] Action: dibuat jika ada orchestration atau business rule kompleks
- [ ] Domain Service: dibuat jika ada business rule reusable
- [ ] Transaction: digunakan untuk operasi multi-tabel
- [ ] Error handling: menggunakan standar Laravel

**Frontend:**

- [ ] Tidak ada perubahan pada data-testid
- [ ] Tidak ada perubahan pada struktur data API
- [ ] Konsistensi penamaan component
- [ ] Typing lengkap (jika TypeScript)

**Testing:**

- [ ] `./vendor/bin/sail test` PASS
- [ ] `./vendor/bin/sail npm run test:e2e` PASS

**Documentation:**

- [ ] Penjelasan layer yang dibuat/tidak dibuat dengan alasan
- [ ] Ringkasan perubahan per modul

---

## 🎯 CONTOH FLOW REFACTOR

### Sebelum (Bad):

```php
// Controller terlalu gemuk
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|unique:departments',
    ]);

    // Business logic di controller
    if (Department::where('name', $validated['name'])->exists()) {
        return response()->json(['error' => 'Duplicate'], 409);
    }

    $department = Department::create($validated);

    return response()->json($department, 201);
}
```

### Sesudah (Good):

```php
/**
 * Store a newly created department.
 *
 * @param \App\Http\Requests\StoreDepartmentRequest $request
 * @return \Illuminate\Http\JsonResponse
 */
public function store(StoreDepartmentRequest $request): JsonResponse
{
    $department = Department::create($request->validated());

    return (new DepartmentResource($department))
        ->response()
        ->setStatusCode(201);
}
```

**Penjelasan:**

- Validasi dipindah ke `StoreDepartmentRequest`
- Unique check sudah di-handle di FormRequest rules
- Response menggunakan Resource
- Controller tipis, hanya orchestration

---

**END OF DOCUMENT**
