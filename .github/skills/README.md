# Agent Skills

Skills adalah panduan terstruktur yang membantu AI dalam menyelesaikan task dengan konsisten dan sesuai arsitektur codebase.

## 🔌 Available MCP Servers

**PENTING**: Gunakan MCP tools, bukan command manual!

| Server | Tools Utama |
|--------|-------------|
| **Laravel Boost** | `database-schema`, `list-routes`, `search-docs`, `tinker`, `last-error`, `read-log-entries`, `browser-logs` |
| **SonarQube MCP** | `get_project_quality_gate_status`, `get_component_measures`, `search_duplicated_files`, `get_duplications` |
| **Filesystem** | `read_file`, `write_file`, `edit_file`, `directory_tree` |
| **Shadcn UI** | `get_component`, `get_component_demo`, `list_blocks`, `get_block` |

### Contoh Penggunaan MCP

```
# Lihat schema database (bukan artisan command)
mcp_laravel-boost_database-schema()

# Cari dokumentasi Laravel
mcp_laravel-boost_search-docs(queries: ["migration foreign key"])

# Ambil baseline quality gate + metrik duplikasi Sonar
mcp_io_github_son_get_project_quality_gate_status(projectKey: "...")
mcp_io_github_son_get_component_measures(projectKey: "...", metricKeys: ["duplicated_lines", "duplicated_blocks", "duplicated_lines_density", "ncloc", "coverage"])

# Ambil komponen UI
mcp_shadcn-ui-mcp-server_get_component(componentName: "table")
```

---

## 📁 Struktur

```
.github/
├── copilot-instructions.md
├── skills/
│   ├── README.md              # Dokumentasi ini
│   ├── DECISION.md            # Matrix pemilihan skill
│   ├── feature-crud-simple/   # CRUD sederhana
│   ├── feature-crud-complex/  # CRUD dengan relasi
│   ├── feature-non-crud/      # Non-CRUD pages
│   ├── refactor-backend/      # Refactor Laravel
│   ├── refactor-frontend/     # Refactor React
│   ├── database-migration/    # Migration, seeder
│   ├── testing-strategy/      # Tests
│   └── session-handoff/       # Checkpoint lintas sesi/laptop
└── prompts/
    ├── create-feature.prompt.md
    ├── create-import.prompt.md
    ├── create-tests.prompt.md
    ├── refactor-module.prompt.md
    ├── refactor-sonar.prompt.md
    ├── continue-progress.prompt.md
    └── checkpoint-progress.prompt.md
```

---

## 📋 Daftar Skills

| Skill | Deskripsi | Tools |
|-------|-----------|-----------|
| `feature-crud-simple` | CRUD 1 tabel | database-schema, list-routes, shadcn |
| `feature-crud-complex` | CRUD + relasi | database-schema, tinker, shadcn |
| `feature-non-crud` | Dashboard, settings | list-routes, shadcn blocks |
| `refactor-backend` | Refactor Laravel | database-schema, list-routes, search-docs |
| `refactor-frontend` | Refactor React | shadcn, browser-logs |
| `database-migration` | Migration | database-schema, search-docs |
| `testing-strategy` | Tests | last-error, read-log-entries |
| `session-handoff` | Checkpoint lintas sesi | read_file, git status, prompt workflow |

---

## 🚀 Quick Start

1. **Pilih skill** → lihat [DECISION.md](./DECISION.md)
2. **Baca SKILL.md** → `mcp_filesystem_read_file(path: ".github/skills/{skill}/SKILL.md")`
3. **Gunakan MCP tools bila relevan**; untuk operasi git gunakan git CLI
4. **Referensi files existing** → bukan template

## 💡 Hemat Token MCP

1. Mulai dari data paling kecil dulu (summary/list/search), jangan langsung full dump.
2. Untuk `database-schema`, gunakan `summary: true` sebelum detail table tertentu.
3. Untuk `search-docs`, gunakan query spesifik + `packages` + `token_limit` minimal.
4. Baca log/error bertahap (entry kecil dulu), tambah hanya jika perlu.
5. Hindari multi-call yang overlap; gabungkan kebutuhan dalam 1 call jika bisa.

---

## 🔄 Workflows

| Command | Deskripsi |
|---------|-----------|
| `/create-feature` | Buat fitur CRUD baru |
| `/create-import` | Tambahkan fitur import pada modul existing |
| `/refactor-module` | Refactor modul existing |
| `/refactor-sonar` | Susun dan jalankan refactor plan berbasis data SonarQube MCP |
| `/create-tests` | Buat test untuk fitur |
| `/continue-progress` | Lanjutkan sesi dari checkpoint terbaru |
| `/checkpoint-progress` | Simpan checkpoint sesi aktif |

Catatan `/refactor-sonar`:
Jika Sonar MCP tidak tersedia, gunakan fallback evidence dari `.sonarcloud.properties`, `coverage.xml`, dan perubahan file Git. Tetap mulai dari ringkasan metrik lalu drill-down ke cluster duplikasi prioritas untuk hemat token.
Progress batch disimpan terpisah di `docs/refactor-sonar-progress.md`, bukan di prompt utama.
Eksekusi disarankan dalam wave semi-besar terkontrol (4-8 file/pola refactor) untuk menyeimbangkan kecepatan dan risiko regresi.
