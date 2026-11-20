# .github/ — CI & GitHub Knowledge Base

## OVERVIEW

GitHub-only configuration: CI/CD workflows and issue templates. Agent rules, skills, commands, and subagents live elsewhere — see root `AGENTS.md` (rules) and `.claude/` (skills/commands/agents shared by OpenCode and Claude CLI).

## STRUCTURE

```
.github/
├── ISSUE_TEMPLATE/
│   └── claim.md             # Template issue kerja (claim → branch → PR)
├── workflows/
│   ├── ci-image.yml         # Docker CI image build
│   ├── issue-lifecycle.yml  # Label status:* otomatis saat PR dibuka/merge
│   ├── release.yml          # Release workflow
│   └── tests.yml            # Test pipeline (Pest + Playwright E2E, 4 shard)
└── AGENTS.md
```

## WHERE TO LOOK

| Task | Location |
|------|----------|
| CI pipeline / E2E shard list | `workflows/tests.yml` |
| Issue status automation | `workflows/issue-lifecycle.yml` |
| Agent rules | root `AGENTS.md` |
| Skills / commands / subagents | `.claude/skills/`, `.claude/commands/`, `.claude/agents/` |

## CONVENTIONS

- Jangan menaruh konfigurasi agent (instructions, prompts, skills) di `.github/` lagi.
- Saat menambah/menghapus direktori E2E, update daftar di step `Run Playwright E2E` (`tests.yml`) dan angka di root `AGENTS.md`.
