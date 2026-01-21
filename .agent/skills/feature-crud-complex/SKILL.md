---
name: Feature CRUD Complex
description: Workflow untuk membuat fitur CRUD kompleks (misal: Employees) dengan relasi, DTO, dan Action Classes.
---

# Feature CRUD Complex

Gunakan skill ini untuk fitur yang melibatkan tabel relasi (belongsTo, hasMany), validasi kompleks, atau logic bisnis yang berat.

## 1. Kriteria "Complex"
- Melibatkan > 1 tabel (relasi).
- Field banyak (>= 3 field bisnis).
- Butuh orkestrasi (misal: simpan ke DB + upload file + kirim email).
- **WAJIB** menggunakan Action Classes dan DTO.

## 2. Struktur Backend (Strict Architecture)
- **Controller**: Hanya facade. Panggil Action.
- **DTO**: `App\Http\DTOs\{Feature}\{Action}Data`. Immutable.
- **Action**: `App\Http\Actions\{Feature}\{Action}Action`. Handle logic & transaction.
- **Request**: Validasi Input.
- **Resource**: Transformasi Output.

### Contoh Controller (Complex)
```php
public function store(StoreEmployeeRequest $request, CreateEmployeeAction $action): JsonResponse
{
    $data = StoreEmployeeData::fromRequest($request);
    $employee = $action->execute($data);
    return (new EmployeeResource($employee))->response()->setStatusCode(201);
}
```

## 3. Struktur Frontend (Inertia + React)
- **Shared Components**: Gunakan komponen reusable seperti `AsyncSelect` untuk relasi (Department/Position).
- **Form Handling**: Handle state kompleks, mungkin perlu `useReducer` atau form library jika sangat besar.

## 4. Langkah Implementasi
1.  **Model & Migration**: Definisikan relasi di Model (`belongsTo`, `hasMany`).
2.  **DTO**: Buat Data Transfer Object.
3.  **Action**: Buat Action Class `Create...`, `Update...`.
4.  **Controller**: Wiring Request -> DTO -> Action.
5.  **Tests**:
    - Unit Test untuk Action.
    - Feature Test untuk Controller.
    - E2E Test (jika krusial).

## 5. Verifikasi
- [ ] `./vendor/bin/sail test` (Test Backend).
- [ ] `./vendor/bin/sail npm run test:e2e` (Test E2E).
