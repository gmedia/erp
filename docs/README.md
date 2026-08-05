# Docs index

Index for agents and developers. Prefer these paths over inventing new status docs.

## Active (keep current)

| Doc | Role |
|-----|------|
| [development-patterns.md](./development-patterns.md) | Canonical implementation patterns (CRUD, reports, FY auto-select) |
| [module-registry.md](./module-registry.md) | E2E + Pest registry — **counts = table/YAML rows** |
| [database/IMPLEMENTATION_STATUS.md](./database/IMPLEMENTATION_STATUS.md) | Implementation status SSOT |
| [refactor-sonar-progress.md](./refactor-sonar-progress.md) | **FROZEN** Sonar batch A–G history |
| `user-guide-*.md` | End-user guides served via `/api/user-guide` |

## Dashboards (do not confuse)

| Surface | Route | Guide |
|---------|-------|-------|
| Home / main | `/dashboard` | [user-guide-dashboard.md](./user-guide-dashboard.md) — entity totals only |
| Financial | `/financial-dashboard` | [user-guide-financial-dashboard.md](./user-guide-financial-dashboard.md) — KPI / YoY |
| Aging AR/AP | `/aging-dashboard` | [user-guide-aging-dashboard.md](./user-guide-aging-dashboard.md) |
| Asset | `/asset-dashboard` | [user-guide-asset-dashboard.md](./user-guide-asset-dashboard.md) |
| Pipeline | `/pipeline-dashboard` | Covered under pipeline docs / registry |

## Design docs

| Doc | Status |
|-----|--------|
| [budget-management-design.md](./budget-management-design.md) | **Implemented** (2026-06-22); Pest OK; E2E pending |
| [profit-loss-by-department-design.md](./profit-loss-by-department-design.md) | Research only |
| [database/\*_design.md](./database/) | Module design specs (see IMPLEMENTATION_STATUS) |

## Archive

- [archive/](./archive/) — historical refactor, SPA migration, Sonar progress archive

## Handoff (repo root)

- `task.md` — active session handoff
- `task.changelog.md` — product changelog
- `task.handoff-archive.md` — archived E2E handoffs
