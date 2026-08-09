# task.md — Active Session Handoff

**Last updated:** 2026-08-09  
**Current milestone:** Visual audit — **T1 DataTable shell v2** (in progress)  
**Branch:** `feat/t1-datatable-shell-v2` (from main `bf5758d8`)  
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

## What changed this session (T1 WIP)

Shared chrome (not committed yet unless agent commits next):

- `DataTableToolbar.tsx` — single flex-wrap row; search + `ml-auto` actions (SHELL-02/03)
- `DataTableCore.tsx` — bulk selection bar + clear; `enableRowSelection`; table border wrap; pagination footer chrome; Button import (SHELL-10/11)
- `PaginationControls.tsx` — responsive footer layout, stronger “Showing…” text (SHELL-11)
- `lib/status-badge.ts` — shared `statusBadgeVariant` / `formatStatusLabel`
- `EmployeeColumns.tsx` + `PurchaseOrderColumns.tsx` — semantic Badge variants (SHELL-06 / EMP-03 / PO-01)
- `BACKLOG.md` — T1 in progress; T2 active-route partial (HF-3)

Sticky Actions (HF-2) **unchanged**.

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

1. `npm run types` (or sail) on touched TS  
2. Optional light smoke: `/employees`, `/purchase-orders`, `/departments`  
3. Commit + push + **one PR** for T1 only  
4. Mark BACKLOG T1 done after merge  

## Continuation Prompt

Finish T1: verify types/lint, commit on `feat/t1-datatable-shell-v2`, open MR with handoff. No Wave 2. Do not include `e2e/`.
