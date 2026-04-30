# Agent Skills

Skills adalah panduan terstruktur yang membantu AI dalam menyelesaikan task dengan konsisten dan sesuai arsitektur codebase.

## 🔌 Available MCP Servers

**PENTING**: Gunakan MCP tools, bukan command manual!

| Server | Tools Utama |
|--------|-------------|
| **Laravel Boost** | `activate_laravel_application_inspection_tools()`, `activate_laravel_database_management_tools()`, `activate_laravel_logging_and_debugging_tools()`, `mcp_laravel-boost_search-docs(...)`, `mcp_laravel-boost_tinker(...)` |
| **Context7** | `mcp_context7_resolve-library-id(...)`, `mcp_context7_query-docs(...)` |
| **Depwire** | `mcp_depwire_get_architecture_summary()`, `mcp_depwire_get_file_context(...)`, `mcp_depwire_impact_analysis(...)`, `mcp_depwire_simulate_change(...)` |
| **SonarQube MCP** | `get_project_quality_gate_status`, `get_component_measures`, `search_duplicated_files`, `get_duplications` |
| **Filesystem / Workspace Tools** | `read_file(...)`, `list_dir(...)`, `grep_search(...)`, `file_search(...)` |
| **Shadcn UI** | `activate_shadcn_ui_component_and_block_listing`, `activate_shadcn_ui_code_retrieval`, `mcp_shadcn-ui_get_component`, `mcp_shadcn-ui_get_block`, `mcp_shadcn-ui_get_component_demo` |

### Contoh Penggunaan MCP

```
# Lihat schema database (bukan artisan command)
activate_laravel_database_management_tools()

# Cari dokumentasi Laravel
mcp_laravel-boost_search-docs(queries: ["migration foreign key"])

# Cari dokumentasi package/framework terbaru
mcp_context7_resolve-library-id(libraryName: "React Router", query: "route object migration")
mcp_context7_query-docs(libraryId: "/remix-run/react-router", query: "route object migration")

# Cek blast radius sebelum refactor struktural
mcp_depwire_get_file_context(filePath: "app/Services/ExampleService.php")
mcp_depwire_impact_analysis(symbol: "ExampleService", file: "app/Services/ExampleService.php")

# Ambil baseline quality gate + metrik duplikasi Sonar
mcp_io_github_son_get_project_quality_gate_status(projectKey: "...")
mcp_io_github_son_get_component_measures(projectKey: "...", metricKeys: ["duplicated_lines", "duplicated_blocks", "duplicated_lines_density", "ncloc", "coverage"])

# Aktifkan retrieval/listing Shadcn UI bila perlu
activate_shadcn_ui_code_retrieval()

# Ambil source komponen UI
mcp_shadcn-ui_get_component(componentName: "table")

# Lihat demo penggunaan komponen UI bila perlu
mcp_shadcn-ui_get_component_demo(componentName: "table")
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
│   ├── feature-import/        # Import Excel pada modul existing
│   ├── feature-non-crud/      # Non-CRUD pages
│   ├── refactor-backend/      # Refactor Laravel
│   ├── refactor-e2e/          # Refactor E2E tests
│   ├── refactor-frontend/     # Refactor React
│   ├── database-migration/    # Migration, seeder
│   ├── testing-strategy/      # Tests
│   └── session-handoff/       # Checkpoint lintas sesi/laptop
├── agents/
│   ├── README.md                    # Kapan memakai custom agent
│   ├── refactor-safe.agent.md       # Refactor aman berbasis Depwire
│   └── context7-research.agent.md   # Lookup docs berbasis Context7
└── prompts/
    ├── create-feature.prompt.md
    ├── create-import.prompt.md
    ├── create-tests.prompt.md
    ├── refactor-module.prompt.md
    ├── refactor-sonar.prompt.md
    ├── refactor-safe-depwire.prompt.md
    ├── context7-docs.prompt.md
    ├── continue-progress.prompt.md
    └── checkpoint-progress.prompt.md
```

---

## 📋 Daftar Skills

| Skill | Deskripsi | Tools |
|-------|-----------|-----------|
| `feature-crud-simple` | CRUD 1 tabel | database-schema, list-routes, shadcn |
| `feature-crud-complex` | CRUD + relasi | database-schema, tinker, shadcn |
| `feature-import` | Import Excel | database-schema, docs, tests |
| `feature-non-crud` | Dashboard, settings | list-routes, shadcn blocks |
| `refactor-backend` | Refactor Laravel | database-schema, list-routes, search-docs |
| `refactor-e2e` | Refactor test browser | database-schema, logging/debugging, read_file |
| `refactor-frontend` | Refactor React | shadcn, Laravel logging/debugging |
| `database-migration` | Migration | database-schema, search-docs |
| `testing-strategy` | Tests | Laravel logging/debugging, tinker |
| `session-handoff` | Checkpoint lintas sesi | read_file, git status, prompt workflow |

## Agents

| Agent | Gunakan Saat | Tools Utama |
|-------|--------------|-------------|
| `Safe Refactor` | Rename/move/split/merge, blast radius, refactor lintas file | `depwire/*`, `context7/*`, read/edit/search/execute |
| `Context7 Research` | Lookup docs package/framework/SDK/API/CLI terbaru | `context7/*`, read/search |

---

## 🚀 Quick Start

1. **Pilih skill** → lihat [DECISION.md](./DECISION.md)
2. **Baca SKILL.md** → mulai dengan `read_file(filePath: "/absolute/path/to/project/.github/skills/{skill}/SKILL.md", startLine: 1, endLine: 250)`, lalu lanjutkan range berikutnya sampai instruksi relevan selesai terbaca
3. **Tentukan MCP utama**: `laravel-boost` untuk surface Laravel project, `context7` untuk docs eksternal, `depwire` untuk impact analysis atau refactor safety
4. **Gunakan MCP tools bila relevan**; untuk operasi git gunakan git CLI
5. **Referensi files existing** → bukan template

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
| `/refactor-safe-depwire` | Rencanakan refactor aman dengan Depwire |
| `/context7-docs` | Lookup docs package/framework terbaru via Context7 |
| `/create-tests` | Buat test untuk fitur |
| `/continue-progress` | Lanjutkan sesi dari checkpoint terbaru |
| `/checkpoint-progress` | Simpan checkpoint sesi aktif |

Catatan `/refactor-sonar`:
Jika Sonar MCP tidak tersedia, gunakan fallback evidence dari `.sonarcloud.properties`, `coverage.xml`, dan perubahan file Git. Tetap mulai dari ringkasan metrik lalu drill-down ke cluster duplikasi prioritas untuk hemat token.
Jika wave refactor bersifat struktural, gunakan Depwire lebih dulu untuk file context, impact analysis, dan simulate change sebelum patch pertama.
Progress batch disimpan terpisah di `docs/refactor-sonar-progress.md`, bukan di prompt utama.
Eksekusi disarankan dalam wave semi-besar terkontrol (4-8 file/pola refactor) untuk menyeimbangkan kecepatan dan risiko regresi.
