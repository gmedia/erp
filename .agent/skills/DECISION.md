# Agent Skills Decision Matrix

Gunakan tabel ini untuk memilih skill yang tepat berdasarkan kebutuhan task.

## 🔌 MCP Tools - Selalu Gunakan Ini!

> **PENTING**: Selalu gunakan MCP tools, bukan command manual. Ini lebih efisien dan menghemat token.

| Kebutuhan | MCP Tool |
|-----------|----------|
| Lihat DB schema | `mcp_laravel-boost_database-schema()` |
| Lihat routes | `mcp_laravel-boost_list-routes()` |
| Cari docs | `mcp_laravel-boost_search-docs(queries: [...])` |
| Test code | `mcp_laravel-boost_tinker(code: "...")` |
| Debug error | `mcp_laravel-boost_last-error()` |
| Ambil komponen UI | `mcp_shadcn-ui-mcp-server_get_component(componentName: "...")` |
| Baca file referensi | `mcp_filesystem_read_file(path: "...")` |

---

## 🎯 Quick Decision Tree

```
Apa yang ingin dilakukan?
│
├── Buat fitur baru
│   ├── CRUD 1 tabel, tanpa FK? ────→ feature-crud-simple
│   ├── CRUD dengan relasi? ────────→ feature-crud-complex
│   └── Non-CRUD (dashboard, dll)? ─→ feature-non-crud
│
├── Refactor kode existing
│   ├── Backend (Laravel)? ─────────→ refactor-backend
│   ├── Frontend (React)? ──────────→ refactor-frontend
│   └── Berbasis temuan Sonar? ─────→ workflow refactor-sonar
│
├── Database
│   └── Migration, seeder? ─────────→ database-migration
│
└── Testing ────────────────────────→ testing-strategy
```

---

## 📊 Decision Matrix

| Kondisi | Skill |
|---------|-------|
| CRUD 1 tabel | `feature-crud-simple` |
| CRUD dengan FK, filter range | `feature-crud-complex` |
| Dashboard, Settings | `feature-non-crud` |
| Perbaiki arsitektur backend | `refactor-backend` |
| Perbaiki struktur komponen | `refactor-frontend` |
| Prioritas refactor dari SonarQube | `workflow refactor-sonar` |
| Migration, seeder | `database-migration` |
| Buat tests | `testing-strategy` |

---

## 🔍 Kriteria Detail

### feature-crud-simple
- ✅ Hanya 1 tabel utama
- ✅ Tidak ada relasi FK
- ✅ Filter hanya search

### feature-crud-complex
- ✅ Ada relasi belongsTo
- ✅ Filter: dropdown, range

### feature-non-crud
- ✅ Tidak ada model baru
- ✅ Custom UI

### refactor-backend
- ✅ Merapikan Controller/Action
- ✅ TIDAK mengubah API

### refactor-frontend
- ✅ Merapikan komponen
- ✅ TIDAK mengubah data-testid

---

## 🚀 Cara Menggunakan

1. **Pilih skill** dari decision tree
2. **Baca SKILL.md**: `mcp_filesystem_read_file(path: ".agent/skills/{skill}/SKILL.md")`
3. **Gunakan MCP tools** sesuai instruksi di SKILL.md
4. **Referensi files existing**, bukan template
