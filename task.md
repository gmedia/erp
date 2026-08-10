# task.md — Active Session Handoff

**Last updated:** 2026-08-10  
**Current milestone:** Visual audit — **T2** shipped as PR (parallel to **T1**)  
**Branch:** `feat/t2-sidebar-density` @ `9847058c`  
**Base main:** `bf5758d8` (HF-1–3 merged)  
**T1 PR:** https://github.com/gmedia/erp/pull/90  
**T2 PR:** https://github.com/gmedia/erp/pull/91  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 + HF-1 #87 · HF-2 #88 · HF-3 #89 on main  
- **T1** DataTable shell v2 — **PR #90** open (`feat/t1-datatable-shell-v2`)  
- **T2** sidebar density/truncation — **PR #91** open (`feat/t2-sidebar-density`)

## Themes

| ID | Status |
|----|--------|
| T1 | PR #90 open |
| **T2** | PR #91 open |
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

1. Human: merge #90 and/or #91 (order flexible; low conflict risk)  
2. After merges: pull main; mark BACKLOG T1/T2 done  
3. Next theme: **T3** page header contract  

## Continuation Prompt

After #90/#91 merge: pull main, update BACKLOG, branch `feat/t3-page-header` for page header contract. No Wave 2 mass capture. Do not wait on CI.
