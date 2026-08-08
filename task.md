# task.md — Active Session Handoff

**Last updated:** 2026-08-08  
**Current milestone:** Visual audit — HF-1 (negative cash semantics) in progress on branch  
**Branch:** `fix/hf1-financial-cash-negative-color`  
**Main tip:** `aee84afe` (chore visual audit Wave 0–1 #86 merged)  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md` (T1–T5 + hotfixes)  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md` (program; stop-rule applied)

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86 merged)
- **HF-1:** signed-balance KPI chrome — Cash / Net Income / Equity use rose when value &lt; 0; `KpiCard.valueClassName`; net cash flow bar rose when negative

## P0 remaining

1. ~~**FD-01** Negative cash balance styled green~~ → HF-1 (this branch)  
2. **EMP-01 / SHELL-07** Wide tables clip Actions → HF-2  
3. **ACC-02 / SHELL-08** Wrong sidebar active on Chart of Accounts → HF-3  

## Themes (user picks next)

T1 DataTable shell · T2 Sidebar · T3 Page header · T4 KPI semantics · T5 Sparse density  

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  

## Recommended next (after HF-1 PR)

1. HF-2 sticky Actions  
2. HF-3 Accounts sidebar active  
3. Then T1 shell if capacity  

## Files (HF-1)

- `resources/js/components/common/KpiCard.tsx`  
- `resources/js/components/financial-dashboard/SummaryCards.tsx`  
- `resources/js/components/financial-dashboard/CashFlowSummary.tsx`  
- `docs/visual-audit/BACKLOG.md`  

## Continuation Prompt

Continue HF-1 PR if open; else implement HF-2 sticky DataTable Actions on a new `fix/hf2-*` branch from main. Do not Wave 2 mass capture.
