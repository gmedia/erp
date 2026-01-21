# Agent Skills

Skills adalah panduan terstruktur yang membantu AI dalam menyelesaikan task dengan konsisten dan sesuai arsitektur codebase.

## 📁 Struktur

```
.agent/
├── skills/
│   ├── README.md              # Dokumentasi ini
│   ├── DECISION.md            # Matrix pemilihan skill
│   │
│   ├── feature-crud-simple/   # CRUD sederhana (1 tabel)
│   ├── feature-crud-complex/  # CRUD dengan relasi & filter
│   ├── feature-non-crud/      # Non-CRUD (dashboard, settings)
│   │
│   ├── refactor-backend/      # Refactor Laravel
│   ├── refactor-frontend/     # Refactor React
│   │
│   ├── database-migration/    # Migration, seeder, factory
│   └── testing-strategy/      # Unit, feature, e2e tests
│
└── workflows/
    ├── create-feature.md      # /create-feature
    ├── refactor-module.md     # /refactor-module
    └── create-tests.md        # /create-tests
```

## 🚀 Quick Start

### 1. Pilih Skill yang Tepat

Lihat [DECISION.md](./DECISION.md) untuk decision tree dan matrix pemilihan skill.

### 2. Baca SKILL.md

Setiap skill folder berisi:
- `SKILL.md` - Panduan lengkap
- `scripts/` - Helper scripts (scaffold, generate, check)
- `resources/` - Template files

### 3. Gunakan Scripts

```bash
# Scaffold (buat folder structure)
bash .agent/skills/feature-crud-simple/scripts/scaffold.sh Category --dry-run

# Generate (buat files dari template)
bash .agent/skills/feature-crud-simple/scripts/generate.sh Category --dry-run

# Check architecture
bash .agent/skills/refactor-backend/scripts/check-architecture.sh Employee
```

## 📋 Daftar Skills

| Skill | Deskripsi | Scripts |
|-------|-----------|---------|
| `feature-crud-simple` | CRUD 1 tabel tanpa relasi | `scaffold.sh`, `generate.sh` |
| `feature-crud-complex` | CRUD dengan FK & filter | `scaffold.sh`, `generate.sh` |
| `feature-non-crud` | Dashboard, settings, matrix | - |
| `refactor-backend` | Refactor Laravel | `check-architecture.sh`, `generate.sh` |
| `refactor-frontend` | Refactor React | - |
| `database-migration` | Migration & seeder | - |
| `testing-strategy` | Unit, feature, e2e tests | - |

## 🔄 Workflows

Gunakan slash commands untuk workflow terintegrasi:

| Command | Deskripsi |
|---------|-----------|
| `/create-feature` | Buat fitur CRUD baru |
| `/refactor-module` | Refactor modul existing |
| `/create-tests` | Buat test untuk fitur |

## 📝 Cara Membuat Skill Baru

1. Buat folder di `.agent/skills/{skill-name}/`
2. Buat `SKILL.md` dengan format:

```markdown
---
name: Skill Name
description: Deskripsi singkat
---

# Skill Name

## 1. Quick Start
...

## 2. Panduan
...
```

3. (Optional) Buat `scripts/` untuk automation
4. (Optional) Buat `resources/` untuk templates
5. Update `DECISION.md` dengan skill baru

## 🛠️ Script Options

Semua scripts mendukung:

| Option | Deskripsi |
|--------|-----------|
| `--help` | Tampilkan bantuan |
| `--dry-run` | Preview tanpa buat files |
| `--all` | Generate semua files |

## 📚 Referensi

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Inertia.js Documentation](https://inertiajs.com)
