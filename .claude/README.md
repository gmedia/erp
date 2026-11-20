# Agent Config (OpenCode & Claude CLI)

Folder ini adalah sumber kebenaran untuk skill, command, dan subagent. Aturan utama ada di root `AGENTS.md`. Tool yang didukung: **OpenCode** dan **Claude CLI**. MCP server tidak dipakai — lihat tabel tooling CLI di `AGENTS.md` → **Agent Tooling**.

## Struktur

```
.claude/
├── README.md                # Dokumen ini
├── settings.json            # Izin bersama Claude CLI (lokal: settings.local.json, gitignored)
├── agents/
│   └── refactor-safe.md     # Subagent refactor aman (blast radius via rg)
├── commands/                # /<nama> — workflow reusable
└── skills/
    ├── DECISION.md          # Matrix pemilihan skill
    ├── wizard.sh            # Menu interaktif skill
    └── <name>/SKILL.md      # + resources/ (template) + scripts/ (generator)
```

- **Claude CLI** membaca `.claude/` secara native.
- **OpenCode** membaca skill dari `.claude/skills/` (path kompatibel Claude); command dan agent didaftarkan ke `opencode.json` oleh `bash scripts/sync-opencode.sh`. Jalankan ulang script itu setelah menambah/menghapus command atau agent.
- Frontmatter skill: `name` = nama folder (kebab-case), `description` = kapan dipakai.

## Skills

| Skill | Kapan dipakai | Tooling utama |
|-------|---------------|---------------|
| `feature-crud-simple` | CRUD 1 tabel | `db:table`, `route:list`, `components/ui` |
| `feature-crud-complex` | CRUD + relasi | `db:table`, tinker, `components/ui` |
| `feature-import` | Import Excel | `db:table`, source `vendor/maatwebsite/excel`, tests |
| `feature-non-crud` | Dashboard, settings, halaman khusus | `route:list`, `components/ui` |
| `refactor-backend` | Refactor Laravel | `db:table`, `route:list`, `check-architecture.sh` |
| `refactor-e2e` | Refactor test browser | `db:table`, trace Playwright, log Laravel |
| `refactor-frontend` | Refactor React | `components/ui`, log Laravel |
| `database-migration` | Migration, seeder, factory | `db:show`/`db:table` |
| `testing-strategy` | Feature/Unit/E2E test | log Laravel, tinker |
| `session-handoff` | Checkpoint lintas sesi/server | `gh issue`, `rtk git status` |

Pemilihan skill: [skills/DECISION.md](./skills/DECISION.md).

## Commands

| Command | Deskripsi |
|---------|-----------|
| `/claim-issue` | Klaim issue GitHub sebelum membuat branch |
| `/continue-progress` | Lanjutkan sesi dari checkpoint issue terbaru |
| `/checkpoint-progress` | Simpan checkpoint sesi ke komentar issue |
| `/finish-claimed-issue` | Selesaikan issue yang sudah diklaim sampai PR |
| `/verify-second-server` | Verifikasi setup koordinasi dari host kedua |
| `/create-feature` | Buat fitur CRUD baru |
| `/create-import` | Tambah fitur import pada modul existing |
| `/create-tests` | Buat test untuk fitur |
| `/implement-with-ui-rules` | Kerjakan UI baru mengikuti `docs/ui-design-rules.md` |
| `/refactor-module` | Refactor modul existing |
| `/refactor-safe` | Refactor struktural aman (blast radius via `rg`) |
| `/refactor-sonar` | Refactor berbasis data SonarCloud API (progress: `docs/refactor-sonar-progress.md`) |

## Subagent

| Agent | Gunakan saat | Jangan dipakai saat |
|-------|--------------|---------------------|
| `refactor-safe` | rename, move, split, merge, delete, refactor lintas file | typo lokal, styling kecil, edit satu file tanpa dependency risk |

Catatan: `AGENTS.md` membatasi subagent (default 0, maks 1) demi stabilitas host — pakai hanya bila diminta user.

## Hemat Token

1. Mulai dari data paling kecil (`db:show`, `rg -l`, `route:list --path=...`), baru detail yang relevan.
2. Baca file per range baris yang relevan, satu per satu.
3. Potong output panjang (`head`, `tail -n`, `jq`).
