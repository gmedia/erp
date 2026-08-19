# task.md — Active Session Handoff

**Last updated:** 2026-08-19  
**Current milestone:** Visual shared-shell themes **closed** (T1–T5, EX, PO-05, FD-02, SHELL-12)  
**Branch:** `main` @ `28abfb84` (#108 merged)  
**Open PRs:** none  

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`

## Product call (2026-08-18)

- **PO-05:** pin **Grand Total** sticky, immediately left of Actions. Landed **#107**.  
- **SHELL-12 / DASH-01:** enrich `/dashboard` (shortcuts + mix). Keep `/financial-dashboard` separate. Do **not** redirect `/` to FD. Landed **#108**.

## Done on main

- T1–T5 · EX · #101–#108 (incl. FD-02, park, product call, PO-05, SHELL-12)  
- Keep `e2e/` untracked

## Do not

- Mass Wave 2 (85 leaves)  
- Commit `e2e/`  
- Wait on CI  
- >3 tools/turn  
- Merge home into financial dashboard  
- Redirect `/` to FD

## Recommended next step

1. **Close visual** — no new MR unless a concrete UI finding or a new domain brief.  
2. Optional local smoke: `VISUAL_AUDIT_PRESET=exceptions` or `dashboards` — do not commit PNGs.  
3. Next feature work needs a product call (e.g. P&L by department is research-only).

## Continuation Prompt

```
Read task.md. #108 SHELL-12 merged. Visual themes closed. Next: no MR unless new brief or screenshot finding.
Do not mass Wave 2. Keep e2e/ untracked. One theme = one MR. Do not wait CI. Do not redirect / to FD.
```
