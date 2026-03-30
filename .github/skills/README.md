# Agent Skills

Skills adalah panduan terstruktur yang membantu AI dalam menyelesaikan task dengan konsisten dan sesuai arsitektur codebase.

## 🔌 Available MCP Servers

**PENTING**: Gunakan MCP tools, bukan command manual!

| Server | Tools Utama |
|--------|-------------|
| **Laravel Boost** | `database-schema`, `list-routes`, `search-docs`, `tinker`, `last-error`, `read-log-entries`, `browser-logs` |
| **Filesystem** | `read_file`, `write_file`, `edit_file`, `directory_tree` |
| **Shadcn UI** | `get_component`, `get_component_demo`, `list_blocks`, `get_block` |

### Contoh Penggunaan MCP

```
# Lihat schema database (bukan artisan command)
mcp_laravel-boost_database-schema()

# Cari dokumentasi Laravel
mcp_laravel-boost_search-docs(queries: ["migration foreign key"])

# Ambil komponen UI
mcp_shadcn-ui-mcp-server_get_component(componentName: "table")
```

---

## 📁 Struktur

```
.github/
├── skills/
│   ├── README.md              # Dokumentasi ini
│   ├── DECISION.md            # Matrix pemilihan skill
│   ├── feature-crud-simple/   # CRUD sederhana
│   ├── feature-crud-complex/  # CRUD dengan relasi
│   ├── feature-non-crud/      # Non-CRUD pages
│   ├── refactor-backend/      # Refactor Laravel
│   ├── refactor-frontend/     # Refactor React
│   ├── database-migration/    # Migration, seeder
│   └── testing-strategy/      # Tests
└── workflows/
    ├── create-feature.md
    ├── refactor-module.md
    └── create-tests.md
```

---

## 📋 Daftar Skills

| Skill | Deskripsi | MCP Tools |
|-------|-----------|-----------|
| `feature-crud-simple` | CRUD 1 tabel | database-schema, list-routes, shadcn |
| `feature-crud-complex` | CRUD + relasi | database-schema, tinker, shadcn |
| `feature-non-crud` | Dashboard, settings | list-routes, shadcn blocks |
| `refactor-backend` | Refactor Laravel | database-schema, list-routes, search-docs |
| `refactor-frontend` | Refactor React | shadcn, browser-logs |
| `database-migration` | Migration | database-schema, search-docs |
| `testing-strategy` | Tests | last-error, read-log-entries |

---

## 🚀 Quick Start

1. **Pilih skill** → lihat [DECISION.md](./DECISION.md)
2. **Baca SKILL.md** → `mcp_filesystem_read_file(path: ".github/skills/{skill}/SKILL.md")`
3. **Gunakan MCP tools** → bukan command manual
4. **Referensi files existing** → bukan template

---

## 🔄 Workflows

| Command | Deskripsi |
|---------|-----------|
| `/create-feature` | Buat fitur CRUD baru |
| `/refactor-module` | Refactor modul existing |
| `/create-tests` | Buat test untuk fitur |
