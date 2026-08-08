# task.md — Active Session Handoff

**Last updated:** 2026-08-08  
**Current milestone:** Visual audit — HF-3 CoA sidebar active (PR pending)  
**Branch:** `fix/hf3-accounts-sidebar-active`  
**Main tip:** `5e9abaa4` (HF-2 #88 merged)  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md` (T1–T5 + hotfixes)  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md` (program; stop-rule applied)

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86 merged)
- **HF-1:** signed-balance KPI chrome (PR #87)
- **HF-2:** sticky `actions` + horizontal scroll (PR #88)
- **HF-3:** nav active via segment match + longest-href wins (`nav-active.ts` + `nav-main.tsx`) — ACC-02 / SHELL-08

## P0 remaining

1. ~~FD-01 / HF-1~~  
2. ~~EMP-01 / SHELL-07 / HF-2~~  
3. ~~ACC-02 / SHELL-08 / HF-3~~ (this branch)

## Themes (user picks next)

T1 DataTable shell · T2 Sidebar · T3 Page header · T4 KPI semantics · T5 Sparse density  

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  

## Recommended next (after HF-3 PR merge)

1. **T1** DataTable shell v2 if capacity  
2. Or **T2** remaining sidebar IA (truncation/density) — active route fixed in HF-3  
3. Optional light re-smoke 3–5 routes with visual-audit config only  

## Files (HF-3)

- `resources/js/lib/nav-active.ts`  
- `resources/js/components/nav-main.tsx`  
- `docs/visual-audit/BACKLOG.md`  

## Continuation Prompt

After HF-3 merge: start T1 DataTable shell or T2 sidebar density on `feat/*` from main. Do not Wave 2 mass capture.
