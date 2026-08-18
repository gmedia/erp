# task.md — Active Session Handoff

**Last updated:** 2026-08-18  
**Current milestone:** Visual audit — FD-02 fiscal year selector  
**Branch:** `fix/fd-02-fiscal-year-selector`  
**Open PRs:** [#104](https://github.com/gmedia/erp/pull/104)  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`

## Done on main

- T1–T5 · #95 closeout · #97 harness presets  
- #96–#100 EX · **#101** docs · **#102** BS-01 · **#103** FINDINGS landed  
- Keep `e2e/` untracked

## This session

- FD-02 shipped as **PR #104** (do not wait on CI)
- FY Select binds only when id exists in list; `keepPreviousData` on dashboard query
- E2E: `#fiscal-year-select` must not show “Select fiscal year” after data load
- FINDINGS/BACKLOG mark FD-02 as this PR

## Do not

- Mass Wave 2 (85 leaves)  
- Commit `e2e/`  
- Wait on CI  
- >3 tools/turn

## Recommended next step

1. After #104 is green: squash-merge, delete branch, `rtk git pull --ff-only` on main. Do not poll.  
2. Residual only with product call: PO-05, SHELL-12.  
3. Optional: `VISUAL_AUDIT_PRESET=exceptions` — do not commit PNGs.

## Continuation Prompt

```
Read task.md. FD-02 is PR #104. Do not poll CI. Do not start mass Wave 2. Keep e2e/ untracked.
If #104 is already merged, pull main. Residuals: PO-05, SHELL-12 only with product call.
```
