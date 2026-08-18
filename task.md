# task.md — Active Session Handoff

**Last updated:** 2026-08-18  
**Current milestone:** Visual residuals after product call — **PO-05 next**  
**Branch:** `main`  
**Open PRs:** none  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`

## Product call (2026-08-18)

- **PO-05:** pin **Grand Total** sticky, immediately left of Actions. Scope: purchase orders; siblings (invoices/bills) if they share the column pattern.  
- **SHELL-12 / DASH-01:** **enrich `/dashboard`** (charts/widgets in the placeholder). Keep `/financial-dashboard` and separate permissions. Do **not** redirect `/` to FD.

## Done on main

- T1–T5 · EX · #101–#105 (incl. FD-02, parked handoff)  
- Keep `e2e/` untracked

## Do not

- Mass Wave 2 (85 leaves)  
- Commit `e2e/`  
- Wait on CI  
- >3 tools/turn  
- Merge home into financial dashboard

## Recommended next step

1. **PO-05 first** — one `feat/*` or `fix/*` MR: sticky money column left of Actions on PO table (then siblings).  
2. **SHELL-12 second** — separate MR: home widgets (needs a short widget list: e.g. counts stay + 1–2 charts or deep-links). Do not start until PO-05 PR exists.  
3. Optional: `VISUAL_AUDIT_PRESET=exceptions` locally — do not commit PNGs.

## Continuation Prompt

```
Read task.md. Product call done: PO-05 sticky Grand Total next; SHELL-12 enrich /dashboard after that.
Do not mass Wave 2. Keep e2e/ untracked. One theme = one MR.
```
