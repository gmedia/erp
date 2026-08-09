# task.md — Active Session Handoff

**Last updated:** 2026-08-09  
**Current milestone:** Visual audit — **T1 DataTable shell v2** → **PR #90**  
**Branch:** `feat/t1-datatable-shell-v2` @ `2b63e4d0` (from main `bf5758d8`)  
**PR:** https://github.com/gmedia/erp/pull/90  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

| ID | PR | Notes |
|----|-----|--------|
| Wave 0–1 | #86 | harness, plan, FINDINGS freeze |
| HF-1 | #87 | signed KPI danger chrome |
| HF-2 | #88 | sticky DataTable Actions + scroll |
| HF-3 | #89 | nav segment match + longest-href (CoA) |

## What changed this session (T1 → PR #90)

Commits on branch (4 atomic):

1. `a2ad8777` feat: semantic status badges for employees and POs  
2. `0c71b883` feat: single-row DataTable toolbar layout  
3. `aec6123e` feat: DataTable bulk bar and pagination chrome  
4. `2b63e4d0` docs: mark T1 DataTable shell in progress  

- `DataTableToolbar.tsx` — single flex-wrap row (SHELL-02/03)  
- `DataTableCore.tsx` — bulk bar + shell (SHELL-10/11)  
- `PaginationControls.tsx` — footer chrome  
- `lib/status-badge.ts` + EMP/PO columns — SHELL-06  
- Sticky Actions (HF-2) **unchanged**  
- `e2e/` left untracked  

Validated: `tsc --noEmit` clean.

## Themes remaining

| ID | Pri | Theme | Status |
|----|-----|-------|--------|
| **T1** | P0–P1 | DataTable shell v2 | **WIP on branch** — finish verify + PR |
| **T2** | P1 | Sidebar residual | Truncation + density only |
| T3 | P1 | Page header contract | open |
| T4 | P0–P1 | Dashboard & KPI residual | open |
| T5 | P1–P2 | Sparse & report density | open |

## Do not

- Wave 2 mass capture (~78 routes)  
- Default `playwright.config.ts` for visual (`migrate:fresh`)  
- Multi-theme one MR  
- Commit untracked `e2e/`

## Recommended next

1. Review/merge **PR #90** when ready (do not block on local CI wait)  
2. After merge: mark BACKLOG T1 done; pull main  
3. Optional: T2 sidebar density **or** light visual re-smoke  
4. **No** Wave 2 mass capture  

## Continuation Prompt

PR #90 open for T1. Merge when green, update BACKLOG T1 → done, then T2 residual or re-smoke. No Wave 2.
