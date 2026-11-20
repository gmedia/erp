# Agent Skills Decision Matrix

Gunakan tabel ini untuk memilih skill yang tepat berdasarkan kebutuhan task.

## 🔧 Tooling (tanpa MCP)

> MCP server tidak dipakai. Gunakan command CLI berikut; semua runtime via Sail.

| Kebutuhan | Command |
|-----------|---------|
| Lihat DB schema | `./vendor/bin/sail artisan db:show` lalu `./vendor/bin/sail artisan db:table {table}` |
| Lihat routes | `./vendor/bin/sail artisan route:list --path=api/{slug}` |
| Cari docs Laravel/package | Source versi terpasang di `vendor/` / `node_modules/`, lalu docs resmi versi terkait |
| Lihat blast radius sebelum refactor | `rg -n '{Symbol}' app routes resources/js tests database config` |
| Rename/move/delete/split/merge | `/refactor-safe`: daftar file dari `rg` dulu, edit, lalu ulangi `rg` + phpstan/types/test |
| Test code | `./vendor/bin/sail artisan tinker --execute='...'` |
| Debug error | `tail -n 50 storage/logs/laravel.log`; E2E: `npx playwright show-trace` |
| Quality gate / metrik / duplikasi Sonar | SonarCloud Web API via `curl` — lihat `AGENTS.md` → Quality Gate |
| Ambil komponen UI | `resources/js/components/ui/`; tambah baru: `npx shadcn@latest add {name}` |

---

## 🎯 Quick Decision Tree

```
Apa yang ingin dilakukan?
│
├── Butuh docs library/framework/API/CLI ─→ source vendor/ + docs resmi versi terpasang
│
├── Analisis dependensi / blast radius
│   └── Rename, move, split, merge ─→ /refactor-safe (rg)
│
├── Buat fitur baru
│   ├── CRUD 1 tabel, tanpa FK? ────→ feature-crud-simple
│   ├── CRUD dengan relasi? ────────→ feature-crud-complex
│   └── Non-CRUD (dashboard, dll)? ─→ feature-non-crud
│
├── Refactor kode existing
│   ├── Backend (Laravel)? ─────────→ refactor-backend
│   ├── Frontend (React)? ──────────→ refactor-frontend
│   └── Berbasis temuan Sonar? ─────→ `/refactor-sonar`
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

| Kondisi | Skill / Workflow |
|---------|-------------|
| Butuh syntax, konfigurasi, atau upgrade notes package | source `vendor/`/`node_modules/` + docs resmi |
| Perlu blast radius atau refactor aman lintas file | `/refactor-safe` / subagent `refactor-safe` |
| CRUD 1 tabel | `feature-crud-simple` |
| CRUD dengan FK, filter range | `feature-crud-complex` |
| Dashboard, Settings | `feature-non-crud` |
| Perbaiki arsitektur backend | `refactor-backend` |
| Perbaiki struktur komponen | `refactor-frontend` |
| Prioritas refactor dari SonarQube | ``/refactor-sonar`` |
| Migration, seeder | `database-migration` |
| Buat tests | `testing-strategy` |
| Pindah laptop/shift, perlu checkpoint rapi | `session-handoff` |

---

## 🔍 Kriteria Detail

### refactor-safe
- ✅ Rename/move/delete/split/merge file atau symbol
- ✅ Ingin tahu dependents atau blast radius (via `rg`)

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

### `/refactor-sonar`
- ✅ Berbasis baseline + delta metrik Sonar
- ✅ Wave semi-besar terkontrol (4-8 file per pola)
- ✅ Wajib update `docs/refactor-sonar-progress.md` setiap wave

### refactor-frontend
- ✅ Merapikan komponen
- ✅ TIDAK mengubah data-testid

---

## 🚀 Cara Menggunakan

1. **Untuk refactor struktural**, petakan pemakai via `rg` (`/refactor-safe`) sebelum edit
2. **Pilih skill** dari decision tree jika task butuh workflow repo
3. **Baca SKILL.md**: mulai dengan `baca .claude/skills/{skill}/SKILL.md (baris 1–250)`, lalu lanjutkan range berikutnya sampai instruksi relevan selesai terbaca
4. **Gunakan tooling CLI** sesuai instruksi di SKILL.md
5. **Referensi files existing**, bukan template
