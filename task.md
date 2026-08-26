# task.md — Active Session Handoff

**Last updated:** 2026-08-26  
**Current milestone:** Visual shared-shell themes **closed**; waiting for new product brief  
**Branch:** `main` (pull before work)  
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
- Handoff commit after #108: `docs: close visual milestone after SHELL-12`  
- Keep `e2e/` untracked

## Local only (not in git)

- `opencode.json` is **gitignored**. Depwire MCP pinned: `npx -y depwire-cli@1.16.0 mcp` (GitHub/npm **v1.16.0**, 2026-08-19).  
- After new OpenCode session: confirm MCP loads 1.16.0; optional re-index `.depwire/` (`formatVersion` 1).  
- `?? e2e/` — do not commit.

## Do not

- Mass Wave 2 (85 leaves)  
- Commit `e2e/`  
- Wait on CI  
- >3 tools/turn  
- Merge home into financial dashboard  
- Redirect `/` to FD  
- Force-add `opencode.json` unless policy changes

## Recommended next step

1. `rtk git checkout main && rtk git pull --ff-only`  
2. **No new MR** unless a concrete UI finding or a new domain brief.  
3. Optional local smoke: `VISUAL_AUDIT_PRESET=exceptions` or `dashboards` — do not commit PNGs.  
4. Next feature work needs a product call (e.g. P&L by department is research-only).

## Continuation Prompt

```
Read task.md. New OpenCode session. Visual themes closed (#108 SHELL-12 on main).
Depwire MCP is local-only pin depwire-cli@1.16.0 in gitignored opencode.json — restart MCP; do not commit opencode.json.
Next: no MR unless new brief or screenshot finding.
Do not mass Wave 2. Keep e2e/ untracked. One theme = one MR. Do not wait CI. Do not redirect / to FD.
```
