---
name: Feature CRUD Simple
description: Workflow untuk membuat fitur CRUD sederhana (Departments, Positions) dengan satu tabel utama tanpa relasi kompleks.
---

# Feature CRUD Simple

Gunakan skill ini untuk membuat fitur CRUD sederhana yang hanya melibatkan satu tabel utama tanpa relasi foreign key atau logika bisnis kompleks.

## 1. Decision Tree: Simple vs Complex?

```
Apakah modul ini:
├── Punya relasi foreign key? → Complex CRUD
├── Butuh filter multi-field (range, date, dropdown)? → Complex CRUD
├── Perlu komponen React terpisah (Form, Filters, Modal)? → Complex CRUD
└── Hanya 1 tabel, filter search saja? → ✅ Simple CRUD
```

**Contoh Simple**: `positions`, `departments` (1 tabel, hanya field name)
**Contoh Complex**: `employees` (relasi ke department/position, filter salary/date)

---

## 2. Quick Start

### Jalankan Scaffold Script
```bash
# Lihat opsi
bash .agent/skills/feature-crud-simple/scripts/scaffold.sh --help

# Dry run (lihat apa yang akan dibuat)
bash .agent/skills/feature-crud-simple/scripts/scaffold.sh Category --dry-run

# Buat struktur folder
bash .agent/skills/feature-crud-simple/scripts/scaffold.sh Category
```

### Generate Files dari Template
```bash
# Lihat opsi
bash .agent/skills/feature-crud-simple/scripts/generate.sh --help

# Dry run
bash .agent/skills/feature-crud-simple/scripts/generate.sh Category --dry-run

# Generate semua files
bash .agent/skills/feature-crud-simple/scripts/generate.sh Category --all
```

### Template Files
Gunakan template dari folder `resources/` sebagai referensi:
- [IndexAction.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-crud-simple/resources/IndexAction.php.template)
- [FilterService.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-crud-simple/resources/FilterService.php.template)
- [routes.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-crud-simple/resources/routes.php.template)

---

## 3. Struktur File yang Harus Dibuat

### Backend (Laravel)

| Layer | Path | Deskripsi |
|-------|------|-----------|
| Model | `app/Models/{Feature}.php` | Eloquent model |
| Controller | `app/Http/Controllers/{Feature}Controller.php` | CRUD + Export |
| Requests | `app/Http/Requests/{Features}/` | Index, Store, Update, Export |
| Resources | `app/Http/Resources/{Features}/` | Resource + Collection |
| Actions | `app/Actions/{Features}/` | IndexAction, ExportAction |
| Domain | `app/Domain/{Features}/` | FilterService (use trait) |
| Exports | `app/Exports/{Features}/` | Excel export class |
| Routes | `routes/{feature}.php` | Route definitions |

### Frontend (React/Inertia)

| Path | Deskripsi |
|------|-----------|
| `resources/js/pages/{features}/index.tsx` | Halaman utama dengan DataTable |

### Tests

| Path | Deskripsi |
|------|-----------|
| `tests/Feature/{Feature}ControllerTest.php` | Integration test |
| `tests/Unit/Actions/{Features}/` | Unit test untuk Actions |
| `tests/Unit/Requests/{Features}/` | Unit test untuk Requests |
| `tests/Unit/Resources/{Features}/` | Unit test untuk Resources |
| `tests/e2e/{features}/` | E2E tests (Playwright) |

---

## 3. Referensi Contoh

Lihat modul `positions` sebagai referensi:

- [PositionController.php](file:///home/ariefn/project/erp/app/Http/Controllers/PositionController.php)
- [IndexPositionsAction.php](file:///home/ariefn/project/erp/app/Actions/Positions/IndexPositionsAction.php)  
- [PositionFilterService.php](file:///home/ariefn/project/erp/app/Domain/Positions/PositionFilterService.php)
- [routes/position.php](file:///home/ariefn/project/erp/routes/position.php)

---

## 4. Langkah Implementasi

### Phase 1: Database & Model
1. Buat migration: `./vendor/bin/sail artisan make:migration create_{features}_table`
2. Buat model: `./vendor/bin/sail artisan make:model {Feature}`
3. Buat factory: `./vendor/bin/sail artisan make:factory {Feature}Factory`

### Phase 2: Backend Logic
4. Buat Requests di `app/Http/Requests/{Features}/`
5. Buat Resources di `app/Http/Resources/{Features}/`
6. Buat FilterService di `app/Domain/{Features}/` (use `BaseFilterService` trait)
7. Buat Actions di `app/Actions/{Features}/`
8. Buat Controller di `app/Http/Controllers/`
9. Buat Export class di `app/Exports/{Features}/`
10. Definisikan routes di `routes/{feature}.php`
11. Include route di `routes/web.php`

### Phase 3: Frontend
12. Buat halaman index di `resources/js/pages/{features}/index.tsx`

### Phase 4: Testing
13. Buat Feature Test untuk Controller
14. Buat Unit Tests untuk Actions, Requests, Resources
15. Buat E2E Tests

---

## 5. Contoh Code Pattern

### Controller Method (Store)
```php
public function store(Store{Feature}Request $request): JsonResponse
{
    ${feature} = {Feature}::create($request->validated());
    
    return (new {Feature}Resource(${feature}))
        ->response()
        ->setStatusCode(201);
}
```

### FilterService (Simple)
```php
<?php

namespace App\Domain\{Features};

use App\Domain\Concerns\BaseFilterService;

class {Feature}FilterService
{
    use BaseFilterService;
}
```

### Route Pattern
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('{features}', function () {
        return Inertia::render('{features}/index');
    })->name('{features}')->middleware('permission:{feature}');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:{feature},true')->group(function () {
        Route::get('{features}', [{Feature}Controller::class, 'index']);
        Route::get('{features}/{{feature}}', [{Feature}Controller::class, 'show']);
        Route::post('{features}', [{Feature}Controller::class, 'store'])
            ->middleware('permission:{feature}.create,true');
        Route::put('{features}/{{feature}}', [{Feature}Controller::class, 'update'])
            ->middleware('permission:{feature}.edit,true');
        Route::delete('{features}/{{feature}}', [{Feature}Controller::class, 'destroy'])
            ->middleware('permission:{feature}.delete,true');
        Route::post('{features}/export', [{Feature}Controller::class, 'export']);
    });
});
```

---

## 6. Verification Checklist

```
// turbo-all
```

- [ ] `./vendor/bin/sail artisan migrate` - Migration berhasil
- [ ] `./vendor/bin/sail test --filter={Feature}` - Tests PASS
- [ ] Buka browser, test CRUD manual
- [ ] `./vendor/bin/sail npm run test:e2e -- --grep={feature}` - E2E PASS
