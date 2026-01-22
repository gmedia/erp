---
name: Feature Custom / Non-CRUD
description: Workflow untuk fitur non-standar seperti Dashboard, Settings, User Management, atau Halaman Khusus.
---

# Feature Custom / Non-CRUD

Gunakan skill ini untuk halaman atau fitur yang tidak mengikuti pola CRUD standar.

## 🔌 MCP Tools yang Digunakan

| Tool | Kapan Digunakan |
|------|-----------------|
| `mcp_laravel-boost_database-schema` | Lihat model/tabel existing |
| `mcp_laravel-boost_list-routes` | Lihat routes existing, plan custom routes |
| `mcp_laravel-boost_search-docs` | Cari dokumentasi Inertia, custom routing |
| `mcp_shadcn-ui-mcp-server_list_blocks` | Cari dashboard/UI blocks |
| `mcp_shadcn-ui-mcp-server_get_block` | Ambil complex UI blocks |
| `mcp_shadcn-ui-mcp-server_get_component` | Ambil komponen UI |
| `mcp_filesystem_read_file` | Baca file referensi |

---

## 🎯 Decision Tree: Kapan Non-CRUD?

```
Fitur ini NON-CRUD jika:
├── Tidak ada model baru
├── Bekerja dengan model existing
├── Custom UI (dashboard, matrix, wizard)
└── Routing tidak pakai Route::resource
```

**Contoh**: `users`, `permissions`, Dashboard

---

## 📊 Jenis Non-CRUD Patterns

| Pattern | Contoh | Ciri |
|---------|--------|------|
| A: Related Entity | `users` | Manage via parent entity |
| B: Matrix View | `permissions` | Many-to-many, bulk update |
| C: Dashboard | Dashboard | Aggregation, charts |

---

## 📁 Struktur File

| Layer | Path |
|-------|------|
| Controller | `app/Http/Controllers/` (custom methods) |
| Actions | `app/Actions/{Feature}/` |
| Routes | `routes/{feature}.php` (custom) |
| Pages | `resources/js/pages/{feature}/` |

---

## 📖 Referensi Pattern

```
# Gunakan MCP untuk baca referensi:
mcp_filesystem_read_file(path: "app/Http/Controllers/UserController.php")
mcp_filesystem_read_file(path: "app/Http/Controllers/PermissionController.php")

# Cari dashboard blocks:
mcp_shadcn-ui-mcp-server_list_blocks(category: "dashboard")
```

| Pattern | File Referensi |
|---------|---------------|
| User Management | `app/Http/Controllers/UserController.php` |
| Permission Matrix | `app/Http/Controllers/PermissionController.php` |
| Custom Routes | `routes/user.php`, `routes/permission.php` |

---

## 🚀 Langkah Implementasi

### Phase 1: Define Scope
1. Tentukan pattern (A, B, atau C)
2. List operasi yang dibutuhkan
3. Desain custom routes

### Phase 2: Backend
4. Buat Controller dengan custom methods
5. Buat Actions untuk business logic
6. Definisikan routes

### Phase 3: Frontend
```
# Ambil UI blocks jika perlu:
mcp_shadcn-ui-mcp-server_get_block(blockName: "dashboard-01")
```

Buat halaman dan komponen custom

### Phase 4: Testing
- Smoke test (halaman bisa dibuka)
- Test interaksi kunci

---

## ✅ Verification

```bash
// turbo-all
./vendor/bin/sail test --filter={Feature}
```

Gunakan `mcp_laravel-boost_list-routes` untuk verify routes terdaftar.
