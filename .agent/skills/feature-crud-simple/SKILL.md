---
name: Feature CRUD Simple
description: Workflow untuk membuat fitur CRUD sederhana (Departments, Positions) dengan satu tabel utama tanpa relasi kompleks.
---

# Feature CRUD Simple

Gunakan skill ini untuk membuat fitur CRUD sederhana yang hanya melibatkan satu tabel utama tanpa relasi foreign key.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | Sebelum buat migration, lihat existing tables |
| `mcp_laravel-boost_list-routes` | Verifikasi routes setelah create |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi Laravel jika ragu |
| `mcp_shadcn-ui-mcp-server_get_component` | Ambil komponen UI (table, form, button) |
| `mcp_filesystem_read_file` | Baca file referensi existing |

---

## 🎯 Decision Tree: Simple vs Complex?

```
Apakah modul ini:
├── Punya relasi foreign key? → Complex CRUD
├── Butuh filter multi-field (range, date, dropdown)? → Complex CRUD
├── Perlu komponen React terpisah (Form, Filters)? → Complex CRUD
└── Hanya 1 tabel, filter search saja? → ✅ Simple CRUD
```

**Contoh Simple**: `positions`, `departments`
**Contoh Complex**: `employees` (relasi + filter kompleks)

---

## 📁 Struktur File

### Backend
| Layer | Path |
|-------|------|
| Model | `app/Models/{Feature}.php` |
| Controller | `app/Http/Controllers/{Feature}Controller.php` |
| Requests | `app/Http/Requests/{Features}/` |
| Resources | `app/Http/Resources/{Features}/` |
| Actions | `app/Actions/{Features}/` |
| Domain | `app/Domain/{Features}/` |
| Routes | `routes/{feature}.php` |

### Frontend
| Path |
|------|
| `resources/js/pages/{features}/index.tsx` |

### Tests
| Path |
|------|
| `tests/Feature/{Feature}ControllerTest.php` |
| `tests/Unit/Actions/{Features}/` |
| `tests/e2e/{features}/` |

---

## 📖 Referensi Pattern

**PENTING**: Selalu baca file existing untuk pattern, bukan template!

```
# Gunakan MCP untuk baca file referensi:
mcp_filesystem_read_file(path: "app/Http/Controllers/PositionController.php")
mcp_filesystem_read_file(path: "app/Actions/Positions/IndexPositionsAction.php")
mcp_filesystem_read_file(path: "routes/position.php")
```

| Pattern | File Referensi |
|---------|---------------|
| Controller | `app/Http/Controllers/PositionController.php` |
| Action | `app/Actions/Positions/IndexPositionsAction.php` |
| FilterService | `app/Domain/Positions/PositionFilterService.php` |
| Routes | `routes/position.php` |
| Frontend Page | `resources/js/pages/positions/index.tsx` |

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
1. Buat Requests (ikuti pola `app/Http/Requests/Positions/`)
2. Buat Resources (ikuti pola `app/Http/Resources/Positions/`)
3. Buat FilterService (use `BaseFilterService` trait)
4. Buat Actions (ikuti pola `app/Actions/Positions/`)
5. Buat Controller
6. Definisikan routes, include di `routes/web.php`

### Phase 3: Frontend
```
# Ambil komponen shadcn jika perlu:
mcp_shadcn-ui-mcp-server_get_component(componentName: "table")
mcp_shadcn-ui-mcp-server_get_component(componentName: "dialog")
```

Buat halaman di `resources/js/pages/{features}/index.tsx` (ikuti pola positions)

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

Gunakan `mcp_laravel-boost_list-routes` untuk verify routes terdaftar.
