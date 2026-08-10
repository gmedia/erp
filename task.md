# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T3 shipped PR #92** (parallel with #90/#91)  
**Branch:** `feat/t3-page-header`  
**Commits:** `102a26dd` · `0000580f` · `ad2e4112`  
**Base main tip:** `bf5758d8` (HF-1–3 landed)  
**Open PRs:** T1 [#90](https://github.com/gmedia/erp/pull/90) · T2 [#91](https://github.com/gmedia/erp/pull/91) · T3 [#92](https://github.com/gmedia/erp/pull/92)  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md` (T1–T5 + hotfixes)  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md` (program; stop-rule applied)

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)
- **HF-1–3** on main (`bf5758d8`)
- **T1** DataTable shell v2 — PR #90 open  
- **T2** Sidebar density — PR #91 open  
- **T3** (this branch):
  - `PageHeader` primitive (`title` / `description` / `actions` / `meta`)
  - Wired: `DashboardPageShell`, `ReportDataTablePage` (+ optional `description`), `FinancialReportPageShell`
  - Pages: stock-movements (RSM-02 description), financial-dashboard (FD-06 “Financial Overview” crumb/title), accounts (ACC-01: crumb Master Data → CoA; single `PageHeader` H1)

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1 | DataTable shell v2 | PR #90 |
| T2 | Sidebar IA residual | PR #91 |
| **T3** | Page header contract | **this branch → PR** |
| T4 | Dashboard & KPI | open after T1–T3 |
| T5 | Sparse & report density | open |

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  
- Commit local untracked `e2e/` junk  
- Wait on CI for #90/#91 (AGENTS: never wait for CI)

## Recommended next

1. Finish T3: `npx tsc --noEmit` (or project types script) → commit → push → `gh pr create`  
2. Human: merge #90 / #91 / T3 when ready  
3. After merges: pull main; mark T1–T3 done; pick **T4**  
4. Optional light re-smoke 3–5 routes with **visual-audit config only**

## Files (T3)

- `resources/js/components/common/PageHeader.tsx`  
- `resources/js/components/common/DashboardPageShell.tsx`  
- `resources/js/components/common/ReportDataTablePage.tsx`  
- `resources/js/components/reports/financial/FinancialReportPageShell.tsx`  
- `resources/js/pages/accounts/index.tsx`  
- `resources/js/pages/stock-movements/index.tsx`  
- `resources/js/pages/financial-dashboard/index.tsx`  
- `docs/visual-audit/BACKLOG.md` · `task.md`

## Continuation Prompt

Ship **T3 PR** if not open; do not block on #90/#91 CI. After human merges T1–T3, pull main and start **T4** or residual. One theme = one branch = one MR. No Wave 2 mass capture. Keep `e2e/` untracked.
