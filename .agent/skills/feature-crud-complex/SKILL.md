---
name: Feature CRUD Complex
description: Workflow untuk membuat fitur CRUD kompleks dengan relasi foreign key, filter multi-field, dan komponen frontend terpisah.
---

# Feature CRUD Complex

Gunakan skill ini untuk fitur CRUD kompleks dengan relasi antar tabel, filter advanced, dan komponen React modular.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | Lihat relasi antar tabel existing |
| `mcp_laravel-boost_tinker` | Test query relationships |
| `mcp_laravel-boost_list-routes` | Verifikasi routes |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi Eloquent relationships |
| `mcp_shadcn-ui-mcp-server_get_component` | Ambil komponen UI |
| `mcp_shadcn-ui-mcp-server_get_component_demo` | Lihat usage patterns |
| `mcp_filesystem_read_file` | Baca file referensi |

---

## 🎯 Decision Tree: Kapan Complex?

```
Modul ini COMPLEX jika:
├── Punya relasi foreign key
├── Butuh filter range (salary, date) atau dropdown
├── Form/View kompleks → perlu komponen terpisah
└── Ada logic bisnis tambahan
```

**Contoh**: `employees` (relasi department/position, filter salary/hire_date)

---

## 📊 Perbedaan dari Simple CRUD

| Aspek | Simple | Complex |
|-------|--------|---------|
| Relasi | Tidak ada | Foreign keys (belongsTo) |
| Filter | Search only | Search + range + dropdown |
| Components | Inline | Terpisah (Form, Filters, Columns) |
| FilterService | Trait saja | Extended `applyAdvancedFilters()` |

---

## 📁 Struktur File

### Backend
| Layer | Path |
|-------|------|
| Model | `app/Models/{Feature}.php` (dengan relasi) |
| Controller | `app/Http/Controllers/{Feature}Controller.php` |
| Requests | `app/Http/Requests/{Features}/` |
| Resources | `app/Http/Resources/{Features}/` |
| Actions | `app/Actions/{Features}/` |
| Domain | `app/Domain/{Features}/` (Extended FilterService) |
| DTOs | `app/DTOs/{Features}/` (jika perlu) |
| Routes | `routes/{feature}.php` |

### Frontend
| Path |
|------|
| `resources/js/pages/{features}/index.tsx` |
| `resources/js/components/{features}/{Feature}Form.tsx` |
| `resources/js/components/{features}/{Feature}Filters.tsx` |
| `resources/js/components/{features}/{Feature}Columns.tsx` |

---

## 📖 Referensi Pattern

**PENTING**: Selalu baca file existing untuk pattern!

```
# Gunakan MCP untuk baca referensi:
mcp_filesystem_read_file(path: "app/Http/Controllers/EmployeeController.php")
mcp_filesystem_read_file(path: "app/Domain/Employees/EmployeeFilterService.php")
mcp_filesystem_read_file(path: "resources/js/components/employees/EmployeeForm.tsx")

# Test relationships:
mcp_laravel-boost_tinker(code: "Employee::with('department')->first()")
```

| Pattern | File Referensi |
|---------|---------------|
| Controller | `app/Http/Controllers/EmployeeController.php` |
| Extended FilterService | `app/Domain/Employees/EmployeeFilterService.php` |
| Form Component | `resources/js/components/employees/EmployeeForm.tsx` |
| Filters Component | `resources/js/components/employees/EmployeeFilters.tsx` |

---

## 🚀 Langkah Implementasi

### Phase 1: Database & Model
```bash
// turbo-all
./vendor/bin/sail artisan make:migration create_{features}_table
./vendor/bin/sail artisan make:model {Feature} -f
./vendor/bin/sail artisan migrate
```

### Phase 2: Backend
1. Buat DTOs jika perlu (ikuti `app/DTOs/Employees/`)
2. Buat Requests, Resources dengan relasi
3. Buat Extended FilterService dengan `applyAdvancedFilters()`
4. Buat Actions, Controller, routes

### Phase 3: Frontend
```
# Ambil komponen jika perlu:
mcp_shadcn-ui-mcp-server_get_component(componentName: "select")
mcp_shadcn-ui-mcp-server_get_component(componentName: "date-picker")
```

Buat komponen terpisah: Form, Filters, Columns (ikuti pola employees)

### Phase 4: Testing
```bash
// turbo-all
./vendor/bin/sail test --filter={Feature}
./vendor/bin/sail npm run test:e2e -- --grep={feature}
```

---

## ✅ Verification

```bash
// turbo-all
./vendor/bin/sail artisan migrate
./vendor/bin/sail test --filter={Feature}
```

Test semua filter combinations di browser.
