---
name: Feature CRUD Simple
description: Workflow untuk membuat fitur CRUD sederhana (misal: Departments, Positions) menggunakan standar Laravel minimalis.
---

# Feature CRUD Simple

Gunakan skill ini untuk membuat fitur CRUD sederhana yang tidak melibatkan logika bisnis kompleks atau orkestrasi multi-tabel.

## 1. Kriteria "Simple"
- Hanya satu tabel utama.
- Tidak ada logic kompleks (hanya Create, Read, Update, Delete).
- Field sedikit (< 5 field penting).
- Tidak menggunakan DTO atau Action Class (cukup Controller + Model).

## 2. Struktur Backend
- **Controller**: Extends `App\Http\Controllers\Controller`. Method `index`, `store`, `update`, `destroy`.
- **Request**: `Store{Feature}Request` dan `Update{Feature}Request` di `App\Http\Requests\{Feature}`.
- **Resource**: `{Feature}Resource` dan `{Feature}Collection` di `App\Http\Resources\{Feature}`.
- **Model**: Langsung dipanggil di Controller.

### Contoh Controller (Simple)
```php
public function store(StoreDepartmentRequest $request): JsonResponse
{
    $department = Department::create($request->validated());
    return (new DepartmentResource($department))->response()->setStatusCode(201);
}
```

## 3. Struktur Frontend (Inertia + React)
- **Pages**: `resources/js/pages/{feature}/index.tsx` (List + Modal Form).
- **Components**: `UseShadcnUI` components. Gunakan `DataTable` untuk list.

## 4. Langkah Implementasi
1.  **Model & Migration**: Buat model dan file migrasi.
2.  **Requests**: Buat FormRequest untuk validasi.
3.  **Controller**: Buat Resource Controller.
4.  **Routes**: Daftarkan di `routes/web.php`.
5.  **Frontend**: Buat halaman index dan form modal.
6.  **Tests**:
    - `Feature/{Feature}ControllerTest.php`
    - Jalankan `./vendor/bin/sail test`

## 5. Verifikasi
- [ ] Pastikan CRUD berjalan lancar di browser.
- [ ] `./vendor/bin/sail test` harus PASS.
