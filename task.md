# task.md — Active Session Handoff

**Last updated:** 2026-08-19  
**Current milestone:** Visual residuals after product call — **SHELL-12 next**  
**Branch:** `main` @ `76ac5477` (#107 merged)  
**Open PRs:** none  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`

## Product call (2026-08-18)

- **PO-05:** pin **Grand Total** sticky, immediately left of Actions. Landed **#107**.  
- **SHELL-12 / DASH-01:** **enrich `/dashboard`** (charts/widgets in the placeholder). Keep `/financial-dashboard` and separate permissions. Do **not** redirect `/` to FD.

## Done on main

- T1–T5 · EX · #101–#107 (incl. FD-02, park, product call, PO-05)  
- Keep `e2e/` untracked

## Do not

- Mass Wave 2 (85 leaves)  
- Commit `e2e/`  
- Wait on CI  
- >3 tools/turn  
- Merge home into financial dashboard  
- Redirect `/` to FD

## Recommended next step

1. **SHELL-12** — one `feat/*` MR from `main`: home widgets. Confirm widget list before coding. Default proposal: keep 4 count cards + fill placeholder with 1–2 charts and/or deep-links (not a copy of financial dashboard).  
2. Optional: `VISUAL_AUDIT_PRESET=exceptions` locally — do not commit PNGs.

## Continuation Prompt

```
Read task.md. #107 merged. Next: SHELL-12 enrich /dashboard (separate MR). Confirm widget list first.
Do not mass Wave 2. Keep e2e/ untracked. One theme = one MR. Do not wait CI. Do not redirect / to FD.
```
