# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T1–T4 merged**; next **T5** or light re-smoke  
**Branch:** `main` @ `ab4cb0d8` (T4 #93 squash)  
**T1–T4:** PRs #90–#93 **merged**  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)  
- **HF-1–3** on main  
- **T1** DataTable shell v2 — **#90**  
- **T2** Sidebar density — **#91**  
- **T3** Page header — **#92**  
- **T4** Dashboard & KPI — **#93** (FD-02/03, BS-02, DASH-01)

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1 | DataTable shell v2 | **merged** (#90) |
| T2 | Sidebar IA residual | **merged** (#91) |
| T3 | Page header contract | **merged** (#92) |
| T4 | Dashboard & KPI | **merged** (#93) |
| **T5** | Sparse & report density | **open** — next implementation theme |

## Do not

- Mass-capture Wave 2 / remaining 78 routes until T5 lands **or** shared-shell re-reviewed  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  
- Commit local untracked `e2e/` junk  
- Wait on CI (AGENTS: never wait for CI)

## Recommended next (pick one)

### A — **T5** (recommended default)
- Branch from main: `feat/t5-sparse-report-density`
- Scope (BACKLOG): SHELL-05, RSM-01 — summary strip / empty panels; report density on `ReportDataTablePage` + sparse list surfaces
- One theme = one PR

### B — Light visual re-smoke (optional before/after T5)
- **Only** `playwright.visual-audit.config.ts` (never default config)
- 1 worker; base `http://127.0.0.1:82`; admin login
- Re-capture Wave 0–1 exception routes only if needed; PNGs gitignored

### C — Shared-shell re-review
- multimodal-looker on post-T1–T4 chrome before unfreezing Wave 2 mass capture

## Continuation Prompt

Main at `ab4cb0d8` (T4 #93 merged). Start **T5** from main or optional visual-audit re-smoke. No Wave 2 mass capture yet. Keep `e2e/` untracked.
