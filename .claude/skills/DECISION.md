# Agent Skills Decision Matrix

Gunakan tabel ini untuk memilih skill yang tepat berdasarkan kebutuhan task.

## 🔌 MCP Tools - Selalu Gunakan Ini!

> **PENTING**: Selalu gunakan MCP tools, bukan command manual. Ini lebih efisien dan menghemat token.

| Kebutuhan | MCP Tool |
|-----------|----------|
| Lihat DB schema | `mcp_laravel-boost_database-schema()` |
| Lihat routes | `mcp_laravel-boost_list-routes()` |
| Cari docs Laravel ecosystem | `mcp_laravel-boost_search-docs(queries: [...])` |
| Cari docs package/framework/SDK/CLI terbaru | `mcp_context7_resolve-library-id(...)` lalu `mcp_context7_query-docs(...)` |
| Lihat blast radius sebelum refactor | `mcp_depwire_get_file_context(filePath: "...")` + `mcp_depwire_impact_analysis(symbol: "...", file: "...")` |
| Simulasikan rename/move/delete/split/merge | `mcp_depwire_simulate_change(operation: "...", ...)` |
| Cek health arsitektur | `mcp_depwire_get_health_score()` atau `mcp_depwire_get_architecture_summary()` |
| Cari dead code | `mcp_depwire_find_dead_code(confidence: "high")` |
| Test code | `mcp_laravel-boost_tinker(code: "...")` |
| Debug error | `activate_laravel_logging_and_debugging_tools()` lalu gunakan tool error/log yang tersedia |
| Quality gate Sonar | `mcp_io_github_son_get_project_quality_gate_status(projectKey: "...")` |
| Metrik duplikasi Sonar | `mcp_io_github_son_get_component_measures(projectKey: "...", metricKeys: [...])` |
| Cluster duplikasi Sonar | `mcp_io_github_son_search_duplicated_files(projectKey: "...")` |
| Ambil komponen UI | `activate_shadcn_ui_code_retrieval()` lalu `mcp_shadcn-ui_get_component(componentName: "...")`; gunakan `mcp_shadcn-ui_get_block(blockName: "...")` untuk block besar |
| Baca file referensi | `read_file(filePath: "/absolute/path/to/project/...", startLine: 1, endLine: 250)` |

---

## 🎯 Quick Decision Tree

```
Apa yang ingin dilakukan?
│
├── Butuh docs library/framework/API/CLI terbaru ─→ Context7
│
├── Analisis dependensi / blast radius
│   └── Rename, move, split, merge, atau health score ─→ Depwire
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
├── Testing ────────────────────────→ testing-strategy
│
└── Lanjutkan lintas laptop/shift ─→ session-handoff
```

---

## 📊 Decision Matrix

| Kondisi | Skill / MCP |
|---------|-------------|
| Butuh syntax, konfigurasi, atau upgrade notes package | `Context7 MCP` |
| Perlu blast radius atau refactor aman lintas file | `Depwire MCP` |
| CRUD 1 tabel | `feature-crud-simple` |
| CRUD dengan FK, filter range | `feature-crud-complex` |
| Dashboard, Settings | `feature-non-crud` |
| Perbaiki arsitektur backend | `refactor-backend` |
| Perbaiki struktur komponen | `refactor-frontend` |
| Prioritas refactor dari SonarQube | `workflow refactor-sonar` |
| Migration, seeder | `database-migration` |
| Buat tests | `testing-strategy` |
| Pindah laptop/shift, perlu checkpoint rapi | `session-handoff` |

---

## 🔍 Kriteria Detail

### Context7
- ✅ Pertanyaan package/framework/SDK/CLI
- ✅ Butuh syntax, konfigurasi, atau docs versi terbaru
- ✅ Upgrade, migration, atau behavior yang version-specific

### Depwire
- ✅ Rename/move/delete/split/merge file atau symbol
- ✅ Ingin tahu dependents, blast radius, atau dependency graph
- ✅ Cek health score, dead code, atau security scan berbasis graph

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

### workflow refactor-sonar
- ✅ Berbasis baseline + delta metrik Sonar
- ✅ Wave semi-besar terkontrol (4-8 file per pola)
- ✅ Wajib update `docs/refactor-sonar-progress.md` setiap wave

### refactor-frontend
- ✅ Merapikan komponen
- ✅ TIDAK mengubah data-testid

---

## 🚀 Cara Menggunakan

1. **Cek dulu MCP inti**: gunakan `Context7` untuk docs package/framework dan `Depwire` untuk impact analysis atau refactor safety
2. **Pilih skill** dari decision tree jika task butuh workflow repo
3. **Baca SKILL.md**: mulai dengan `read_file(filePath: "/absolute/path/to/project/.github/skills/{skill}/SKILL.md", startLine: 1, endLine: 250)`, lalu lanjutkan range berikutnya sampai instruksi relevan selesai terbaca
4. **Gunakan MCP tools** sesuai instruksi di SKILL.md; untuk refactor struktural, jalankan Depwire sebelum edit
5. **Referensi files existing**, bukan template
