---
name: Feature CRUD Complex
description: Workflow untuk membuat fitur CRUD kompleks dengan relasi foreign key, filter multi-field, dan komponen frontend terpisah.
---

# Feature CRUD Complex

Gunakan skill ini untuk membuat fitur CRUD kompleks yang melibatkan relasi antar tabel, filter advanced, dan komponen React modular.

## 1. Decision Tree: Kapan Complex?

```
Modul ini COMPLEX jika salah satu berikut:
├── Punya relasi foreign key ke tabel lain
├── Butuh filter range (salary, date) atau dropdown (department)
├── Form/View cukup kompleks → perlu komponen terpisah
├── Ada logic bisnis tambahan (sync permissions, calculations)
└── Perlu DTO untuk data transformation
```

**Contoh Complex**: `employees` (relasi department/position, filter salary/hire_date, 4 components)

---

## 2. Perbedaan dari Simple CRUD

| Aspek | Simple | Complex |
|-------|--------|---------|
| Relasi | Tidak ada | Foreign keys (belongsTo) |
| Filter | Search only | Search + range + dropdown |
| DTOs | Tidak perlu | Ada untuk update data |
| Components | Inline di page | Terpisah (Form, Filters, Columns, Modal) |
| FilterService | Trait saja | Extended dengan `applyAdvancedFilters()` |
| Actions | Index + Export | Bisa ada Actions tambahan |

---

## 3. Quick Start

### Jalankan Scaffold Script
```bash
# Lihat opsi
bash .agent/skills/feature-crud-complex/scripts/scaffold.sh --help

# Dry run (lihat apa yang akan dibuat)
bash .agent/skills/feature-crud-complex/scripts/scaffold.sh Product --dry-run

# Buat struktur folder
bash .agent/skills/feature-crud-complex/scripts/scaffold.sh Product
```

### Generate Files dari Template
```bash
# Lihat opsi
bash .agent/skills/feature-crud-complex/scripts/generate.sh --help

# Dry run
bash .agent/skills/feature-crud-complex/scripts/generate.sh Product --dry-run

# Generate semua files
bash .agent/skills/feature-crud-complex/scripts/generate.sh Product --all
```

### Template Files
Gunakan template dari folder `resources/` sebagai referensi:
- [ExtendedFilterService.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-crud-complex/resources/ExtendedFilterService.php.template)
- [UpdateData.php.template](file:///home/ariefn/project/erp/.agent/skills/feature-crud-complex/resources/UpdateData.php.template)
- [Columns.tsx.template](file:///home/ariefn/project/erp/.agent/skills/feature-crud-complex/resources/Columns.tsx.template)

---

## 4. Struktur File yang Harus Dibuat

### Backend (Laravel)

| Layer | Path | Deskripsi |
|-------|------|-----------|
| Model | `app/Models/{Feature}.php` | Dengan relasi belongsTo |
| Controller | `app/Http/Controllers/{Feature}Controller.php` | CRUD + Export + custom methods |
| Requests | `app/Http/Requests/{Features}/` | Termasuk request untuk custom actions |
| Resources | `app/Http/Resources/{Features}/` | Resource dengan relasi |
| Actions | `app/Actions/{Features}/` | Index, Export, + custom actions |
| Domain | `app/Domain/{Features}/` | Extended FilterService |
| **DTOs** | `app/DTOs/{Features}/` | Data Transfer Objects |
| Exports | `app/Exports/{Features}/` | Excel export |
| Routes | `routes/{feature}.php` | Standard + custom routes |

### Frontend (React/Inertia)

| Path | Deskripsi |
|------|-----------|
| `resources/js/pages/{features}/index.tsx` | Halaman utama |
| `resources/js/components/{features}/{Feature}Form.tsx` | Form create/edit |
| `resources/js/components/{features}/{Feature}Filters.tsx` | Advanced filters |
| `resources/js/components/{features}/{Feature}Columns.tsx` | DataTable columns |
| `resources/js/components/{features}/{Feature}ViewModal.tsx` | Detail view modal |

### Tests

Sama seperti Simple CRUD + tests untuk custom actions.

---

## 5. Referensi Contoh

Lihat modul `employees` sebagai referensi:

- [EmployeeController.php](file:///home/ariefn/project/erp/app/Http/Controllers/EmployeeController.php)
- [IndexEmployeesAction.php](file:///home/ariefn/project/erp/app/Actions/Employees/IndexEmployeesAction.php)
- [EmployeeFilterService.php](file:///home/ariefn/project/erp/app/Domain/Employees/EmployeeFilterService.php)
- [UpdateEmployeeData.php (DTO)](file:///home/ariefn/project/erp/app/DTOs/Employees/UpdateEmployeeData.php)
- [EmployeeForm.tsx](file:///home/ariefn/project/erp/resources/js/components/employees/EmployeeForm.tsx)
- [EmployeeFilters.tsx](file:///home/ariefn/project/erp/resources/js/components/employees/EmployeeFilters.tsx)

---

## 6. Langkah Implementasi

### Phase 1: Database & Model
1. Buat migration dengan foreign keys
2. Buat model dengan relasi `belongsTo`
3. Buat factory dengan relasi

### Phase 2: Backend Logic
4. Buat DTOs jika perlu data transformation
5. Buat Requests (termasuk untuk custom actions)
6. Buat Resources dengan relasi di `toArray()`
7. Buat Extended FilterService dengan `applyAdvancedFilters()`
8. Buat Actions (Index, Export, custom)
9. Buat Controller
10. Buat Export class
11. Definisikan routes (standard + custom)

### Phase 3: Frontend
12. Buat komponen terpisah:
    - `{Feature}Columns.tsx` - column definitions
    - `{Feature}Filters.tsx` - filter UI
    - `{Feature}Form.tsx` - create/edit form
    - `{Feature}ViewModal.tsx` - detail modal
13. Buat halaman index yang menggunakan komponen

### Phase 4: Testing
14. Buat Feature Test
15. Buat Unit Tests untuk semua Actions
16. Buat E2E Tests

---

## 7. Contoh Code Pattern

### Extended FilterService
```php
<?php

namespace App\Domain\{Features};

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;

class {Feature}FilterService
{
    use BaseFilterService;

    public function applyAdvancedFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['salary_min'])) {
            $query->where('salary', '>=', $filters['salary_min']);
        }

        if (!empty($filters['salary_max'])) {
            $query->where('salary', '<=', $filters['salary_max']);
        }

        // Date range filters...
    }
}
```

### Model dengan Relasi
```php
class {Feature} extends Model
{
    protected $fillable = ['name', 'department_id', 'position_id', ...];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
```

### Resource dengan Relasi
```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'department' => new DepartmentResource($this->whenLoaded('department')),
        'position' => new PositionResource($this->whenLoaded('position')),
        // ...
    ];
}
```

---

## 8. Verification Checklist

```
// turbo-all
```

- [ ] `./vendor/bin/sail artisan migrate` - Migration berhasil
- [ ] `./vendor/bin/sail test --filter={Feature}` - Tests PASS
- [ ] Test semua filter combinations di browser
- [ ] `./vendor/bin/sail npm run test:e2e -- --grep={feature}` - E2E PASS
