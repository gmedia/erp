# task.md — Active Session Handoff

**Last updated:** 2026-08-08  
**Current milestone:** Visual audit — HF-3 shipped as **PR #89** (await user merge)  
**Branch:** `fix/hf3-accounts-sidebar-active`  
**Commits:** `d4b85789` (fix nav-active) · `32ed7582` (docs)  
**PR:** https://github.com/gmedia/erp/pull/89  
**Main tip (pre-merge):** `5e9abaa4` (HF-2 #88)  
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
- **HF-3:** nav active via segment match + longest-href wins — **PR #89 open** (ACC-02 / SHELL-08)

## P0 remaining

1. ~~FD-01 / HF-1~~  
2. ~~EMP-01 / SHELL-07 / HF-2~~  
3. ~~ACC-02 / SHELL-08 / HF-3~~ (merge PR #89)

## Themes (user picks next after #89 merge)

| ID | Theme | Why next |
|----|-------|----------|
| **T1** | DataTable shell v2 | Highest blast radius: SHELL-02,03,06,07,10,11; EMP-*; PO-* (sticky Actions already in HF-2 — rest of shell) |
| **T2** | Sidebar IA residual | Truncation + density only (active route done in HF-3) |
| T3 | Page header contract | breadcrumb/title drift |
| T4 | Dashboard & KPI | residual after HF-1 |
| T5 | Sparse & report density | lower pri |

## Do not

- Mass-capture Wave 2 / remaining 78 routes  
- Use default `playwright.config.ts` for visual (migrate:fresh)  
- Redesign all 85 modules in one MR  
- Commit local untracked `e2e/` junk  
- Start T1/T2 on top of unmerged HF-3 branch (new branch from **main after merge**)

## Recommended next

1. **You:** review/merge **PR #89** (manual smoke: `/accounts` → CoA active, not Department)  
2. **Agent after merge:** `rtk git checkout main && rtk git pull --ff-only` → branch `feat/t1-datatable-shell-v2` **or** `feat/t2-sidebar-density`  
3. Prefer **T1** if capacity (shared table chrome); **T2** if small residual sidebar polish only  
4. Optional light re-smoke 3–5 routes with visual-audit config only  

## Files (HF-3 / PR #89)

- `resources/js/lib/nav-active.ts`  
- `resources/js/components/nav-main.tsx`  
- `docs/visual-audit/BACKLOG.md`  

## Continuation Prompt

After PR #89 merge: pull main, start **T1 DataTable shell v2** (or T2 sidebar density if user prefers small). One theme = one branch = one MR. Do not Wave 2 mass capture.
