# task.md — Active Session Handoff

**Last updated:** 2026-08-18  
**Current milestone:** Visual audit — **parked** (shared shell + EX + FD-02 on main)  
**Branch:** `main`  
**Open PRs:** none for visual-audit  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`

## Done on main

- T1–T5 · #95 closeout · #97 harness presets  
- #96–#100 EX · **#101** docs · **#102** BS-01 · **#103** FINDINGS  
- **#104** FD-02 FY selector bind + `keepPreviousData`  
- Keep `e2e/` untracked

## This session

- #104 squash-merged; local `main` pulled  
- Visual-audit themes closed except product residuals

## Do not

- Mass Wave 2 (85 leaves)  
- Commit `e2e/`  
- Wait on CI  
- >3 tools/turn  
- Implement PO-05 or SHELL-12 without a product call

## Recommended next step

1. Product call: **PO-05** (Grand Total column), **SHELL-12** (home stub vs financial dashboard). If both no → visual-audit closed.  
2. Optional AI work on `main`: one small Sonar wave (HIGH/BLOCKER or worst coverage) — separate MR, no FY selector.  
3. Optional: `VISUAL_AUDIT_PRESET=exceptions` locally — do not commit PNGs.

## Continuation Prompt

```
Read task.md. Visual audit is parked on main after #104. Do not start mass Wave 2. Keep e2e/ untracked.
Residuals PO-05 / SHELL-12 only with product call. Next AI theme: Sonar on main if requested, else stop.
```
