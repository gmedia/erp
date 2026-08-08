# task.md — Active Session Handoff

**Last updated:** 2026-08-08  
**Current milestone:** Visual audit — HF-2 sticky DataTable Actions (PR pending)  
**Branch:** `fix/hf2-datatable-sticky-actions`  
**Main tip:** `5245260d` (HF-1 #87 merged)  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md` (T1–T5 + hotfixes)  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md` (program; stop-rule applied)

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86 merged)
- **HF-1:** signed-balance KPI chrome — Cash / Net Income / Equity rose when &lt; 0 (PR #87)
- **HF-2:** sticky `actions` column + allow horizontal scroll on shared `DataTable` (`DataTableCore`); `createActionsColumn` size 56

## P0 remaining

1. ~~FD-01 / HF-1~~  
2. ~~EMP-01 / SHELL-07 / HF-2~~ (this branch)  
3. **ACC-02 / SHELL-08** Wrong sidebar active on Chart of Accounts → **HF-3**

## Themes (user picks next)

T1 DataTable shell · T2 Sidebar · T3 Page header · T4 KPI semantics · T5 Sparse density  

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  

## Recommended next (after HF-2 PR merge)

1. HF-3 Accounts sidebar active  
2. Then T1 shell if capacity  

## Files (HF-2)

- `resources/js/components/common/DataTableCore.tsx`  
- `resources/js/utils/columns.tsx`  
- `docs/visual-audit/BACKLOG.md`  

## Continuation Prompt

After HF-2 merge: implement HF-3 CoA sidebar active on `fix/hf3-*` from main. Do not Wave 2 mass capture.
