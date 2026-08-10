# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T2** sidebar density (parallel to **T1 PR #90**)  
**Branch:** `feat/t2-sidebar-density`  
**Base main:** `bf5758d8` (HF-1–3 merged)  
**T1 PR (separate):** https://github.com/gmedia/erp/pull/90  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 + HF-1 #87 · HF-2 #88 · HF-3 #89 on main  
- **T1** DataTable shell v2 — **PR #90** open (`feat/t1-datatable-shell-v2`)  
- **T2** (this branch): sidebar width 17.5rem; denser subnav; stronger active child (font + inset bar); label truncate + tooltips while expanded (not only icon mode)

## Themes

| ID | Status |
|----|--------|
| T1 | PR #90 open |
| **T2** | **in progress** this branch |
| T3–T5 | open |

## Do not

- Mass Wave 2 capture  
- Default playwright visual (`migrate:fresh`)  
- Commit untracked `e2e/`  
- Wait on CI; one theme = one branch = one MR  

## Files (T2)

- `resources/js/components/ui/sidebar.tsx`  
- `resources/js/components/nav-main.tsx`  
- `docs/visual-audit/BACKLOG.md`  

## Recommended next

1. Finish commit + push + `gh pr create` for T2  
2. Human: merge #90 then T2 (or reverse — low conflict risk)  
3. Next theme: **T3** page header  

## Continuation Prompt

Ship T2 PR if not open; after merge pull main; start T3 page header contract. No Wave 2 mass capture.
