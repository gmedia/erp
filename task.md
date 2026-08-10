# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T3** PR #92 after **#90** + **#91** on main  
**Branch:** `feat/t3-page-header`  
**T1:** PR #90 **merged**  
**T2:** PR #91 **merged**  
**T3 PR:** https://github.com/gmedia/erp/pull/92  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)  
- **HF-1–3** on main  
- **T1** DataTable shell v2 — **PR #90 merged**  
- **T2** Sidebar density — **PR #91 merged**  
- **T3** (this branch / PR #92):
  - `PageHeader` primitive (`title` / `description` / `actions` / `meta`)
  - Wired: `DashboardPageShell`, `ReportDataTablePage` (+ optional `description`), `FinancialReportPageShell`
  - Pages: stock-movements (RSM-02), financial-dashboard (FD-06 “Financial Overview”), accounts (ACC-01)

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1 | DataTable shell v2 | **merged** (#90) |
| T2 | Sidebar IA residual | **merged** (#91) |
| **T3** | Page header contract | **PR #92** |
| T4 | Dashboard & KPI | open after T3 |
| T5 | Sparse & report density | open |

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  
- Commit local untracked `e2e/` junk  
- Wait on CI (AGENTS: never wait for CI)

## Recommended next

1. Human: merge **#92** when ready  
2. After merge: pull main; mark T3 done; pick **T4**  
3. Optional light re-smoke with **visual-audit config only**

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

T1 (#90) and T2 (#91) merged. Resolve #92 docs conflicts if needed, push, human merge #92. Then **T4**. No Wave 2 mass capture. Keep `e2e/` untracked.
