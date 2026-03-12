---
name: Refactor Backend
description: Panduan refactor khusus Backend (Laravel)
---

# Refactor Backend (Laravel)

Lakukan refactor kode backend secara **TERKONTROL** tanpa merusak API contract, response structure, atau test existing.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | **WAJIB** cek schema sebelum refactor Model |
| `mcp_laravel-boost_list-routes` | Verifikasi routes tidak berubah |
| `mcp_laravel-boost_search-docs` | Cari best practices Laravel |
| `mcp_laravel-boost_tinker` | Test query Eloquent sebelum commit |
| `mcp_laravel-boost_last-error` | Debug jika test gagal |
| `mcp_filesystem_read_file` | Baca file referensi |

---

## 🚫 ATURAN API COMPATIBILITY

### DILARANG Mengubah:
- ❌ Route URI, HTTP method
- ❌ Request/Response payload structure
- ❌ Response JSON keys
- ❌ Query parameter names

### Yang DIPERBOLEHKAN:
- ✅ Refactor internal (pemindahan logic antar layer)
- ✅ Penambahan typing, Resource, FormRequest

---

## 🏗️ ARSITEKTUR BACKEND

### 1. Controller (Tipis!)
Controller HANYA BOLEH:
- Menerima FormRequest
- Mapping ke DTO (jika perlu)
- Memanggil Action
- Return Resource

### 2. FormRequest
- WAJIB spesifik per operasi: `StoreDepartmentRequest`, `UpdateEmployeeRequest`
- Rules sesuai database schema

### 3. DTO (Opsional)
Gunakan jika:
- ≥ 3 field bermakna bisnis
- Data melewati lebih dari satu layer

### 4. Action (Opsional)
Gunakan jika:
- Ada orchestration multi-tabel
- Ada business rule kompleks

### 5. Domain Service (Opsional)
Gunakan jika:
- Ada aturan bisnis reusable

---

## 📖 Referensi Pattern

```
# WAJIB cek schema sebelum refactor:
mcp_laravel-boost_database-schema()

# Baca file existing untuk pattern:
mcp_filesystem_read_file(path: "app/Http/Controllers/PositionController.php")
mcp_filesystem_read_file(path: "app/Http/Requests/Positions/StorePositionRequest.php")
```

| Pattern | File Referensi |
|---------|---------------|
| Thin Controller | `app/Http/Controllers/PositionController.php` |
| FormRequest | `app/Http/Requests/Positions/StorePositionRequest.php` |
| Resource | `app/Http/Resources/Positions/PositionResource.php` |

---

## 💻 ATURAN CODING

### Controller Signatures
```php
// ✅ BENAR
public function store(StoreDepartmentRequest $request): JsonResponse
public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse

// ❌ SALAH  
public function store(Request $request) // tidak spesifik
```

### Response & Status Codes
| Method | Status Code |
|--------|-------------|
| `store()` | 201 Created |
| `update()` | 200 OK |
| `destroy()` | 204 No Content |

### PHPDoc WAJIB
```php
/**
 * Store a newly created department.
 *
 * @param \App\Http\Requests\StoreDepartmentRequest $request
 * @return \Illuminate\Http\JsonResponse
 */
```

### Import & FQCN Hygiene
```php
// ✅ BENAR
use App\Models\CoaVersion;
use Illuminate\Validation\Rule;

$activeVersion = CoaVersion::where('status', 'active')->first();
'code' => ['required', Rule::unique('approval_flows')->ignore($this->approval_flow)];

// ❌ SALAH
$activeVersion = \App\Models\CoaVersion::where('status', 'active')->first();
'code' => ['required', \Illuminate\Validation\Rule::unique('approval_flows')->ignore($this->approval_flow)];
```

- Terapkan aturan ini juga pada factory, migration, seeder, dan test.
- FQCN tetap boleh dipakai di PHPDoc, generic annotations, dan `::class` metadata.

---

## ⚠️ ANTI OVER-ENGINEERING

JANGAN buat DTO/Action/Domain Service jika:
- Logic hanya CRUD sederhana
- Tidak ada orchestration
- Hanya save model tanpa transformasi

---

## ✅ Verification

```bash
// turbo-all
./vendor/bin/sail test
```

Jika test gagal:
```
mcp_laravel-boost_last-error()
```

**Gunakan `mcp_laravel-boost_list-routes` untuk verify routes tidak berubah.**

---

## 📋 CHECKLIST

- [ ] Controller: tipis, signature jelas, PHPDoc lengkap
- [ ] FormRequest: spesifik, rules sesuai schema
- [ ] Resource: konsisten dengan response lama
- [ ] Test PASS: `./vendor/bin/sail test`
