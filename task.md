# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — resolve **PR #91** (T2) conflicts after **#90** merged; **T3** is PR #92  
**Branch:** `feat/t2-sidebar-density` (merging `origin/main`)  
**T1:** PR #90 **merged**  
**T2 PR:** https://github.com/gmedia/erp/pull/91  
**T3 PR:** https://github.com/gmedia/erp/pull/92  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 + HF-1–3 on main  
- **T1** DataTable shell v2 — **PR #90 merged**  
- **T2** sidebar density/truncation — PR #91 (conflict fix in progress after #90)  
- **T3** page header — PR #92 open (`feat/t3-page-header`)

## Themes

| ID | Status |
|----|--------|
| T1 | **merged** (#90) |
| **T2** | PR #91 — conflict resolve → re-push |
| T3 | PR #92 open |
| T4–T5 | open |

## Do not

- Mass Wave 2 capture  
- Default playwright visual (`migrate:fresh`)  
- Commit untracked `e2e/`  
- Wait on CI; one theme = one branch = one MR  

## Files (T2)

- `resources/js/components/ui/sidebar.tsx`  
- `resources/js/components/nav-main.tsx`  
- `docs/visual-audit/BACKLOG.md` · `task.md` (conflict resolution)

## Recommended next

1. Finish merge of `origin/main` into `feat/t2-sidebar-density` → push → human merge #91  
2. Refresh #92 on main after #91 if needed  
3. After T2/T3: **T4** dashboard/KPI  

## Continuation Prompt

T1 (#90) merged. Fix #91 conflicts (docs only expected), push T2, merge #91 then #92. No Wave 2 mass capture.
