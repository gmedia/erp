# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T4** dashboard & KPI semantics  
**Branch:** `feat/t4-dashboard-kpi-semantics`  
**T4 PR:** https://github.com/gmedia/erp/pull/93  
**T1–T3:** PRs #90–#92 **merged** on main  
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
- **T3** Page header — **PR #92 merged**  
- **T4** (this branch, code landed, not yet committed/PR’d):
  - **FD-02:** sync `fiscal_year_id` query from API `selected_year_id` when URL empty; selector uses `resolvedYearId`
  - **FD-03:** `SummaryCards` `showComparison` — hide % badges / “vs comparison period” when Compare=None
  - **BS-02:** `getSignedAmountTextClass` on balance + section totals; change colors → rose/emerald dark-aware
  - **DASH-01 / SHELL-12:** home `/dashboard` → `DashboardPageShell` + `KpiCard` grid + lighter placeholder

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1 | DataTable shell v2 | **merged** (#90) |
| T2 | Sidebar IA residual | **merged** (#91) |
| T3 | Page header contract | **merged** (#92) |
| **T4** | Dashboard & KPI | **PR #93** |
| T5 | Sparse & report density | open |

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  
- Commit local untracked `e2e/` junk  
- Wait on CI (AGENTS: never wait for CI)

## Validated

- `npm run types` (tsc --noEmit) — clean after T4 edits

## Recommended next

1. Human merge **#93**  
2. Then **T5** or light re-smoke (visual-audit config only)

## Files (T4)

- `resources/js/pages/financial-dashboard/index.tsx`  
- `resources/js/components/financial-dashboard/SummaryCards.tsx`  
- `resources/js/components/reports/financial/FinancialReportSection.tsx`  
- `resources/js/pages/dashboard.tsx`  
- `docs/visual-audit/BACKLOG.md` · `task.md`

## Continuation Prompt

T1–T3 merged. T4 **PR #93** open (FD-02/03, BS-02, DASH-01). Human merge → T5. No Wave 2. Keep `e2e/` untracked.
