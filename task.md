# task.md — Active Session Handoff

**Last updated:** 2026-08-11  
**Current milestone:** Visual audit — resolve #98 / #99 conflicts after #96 merge  
**Branch tip (this work):** `fix/va-asset-models-capture-auth` (rebasing onto main)  
**main tip:** includes **#96** My Approvals + **#97** harness (`361f28cb`)  
**Open PRs:** [#98](https://github.com/gmedia/erp/pull/98) capture auth · [#99](https://github.com/gmedia/erp/pull/99) asset profile  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)  
- **HF-1–3** · **T1–T5** #90–#94 · **Closeout** #95 · **Harness** #97 · **My Approvals** #96 — **on main**  
- Full re-smoke **84/85** historically (`/asset-models` → login)  
- **Capture auth settle** — this branch / **PR #98**  
  - `requireDashboard: true` + re-login retry on top of #97 presets  
  - Local 3-route PASS pre-rebase  

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** |
| Harness | Named presets | **merged** (#97) |
| EX-1 | My Approvals inbox | **merged** (#96) |
| EX-auth | Capture auth settle | **PR #98** (rebasing) |
| EX-asset-profile | Asset profile densify | **PR #99** |

## Do not

- Mass-capture Wave 2 / remaining ~78 routes  
- Use default `playwright.config.ts` for visual (`migrate:fresh`)  
- Commit untracked `e2e/`  
- Wait on CI  
- Parallel tool fan-out (≤3 tools/turn; post-kill = 1)  

## Validated (pre-rebase)

```bash
VISUAL_AUDIT=1 VISUAL_AUDIT_ROUTES=/asset-models,/asset-categories,/dashboard \
  PLAYWRIGHT_BASE_URL=http://127.0.0.1:82 PLAYWRIGHT_WORKERS=1 \
  npx playwright test -c playwright.visual-audit.config.ts
# → 3 passed (re-run after rebase if needed)
```

## Recommended next

1. Finish rebase #98 → force-with-lease push (MERGEABLE).  
2. Rebase #99 onto main → resolve `task.md`/`BACKLOG` → push.  
3. Merge #98 / #99 when ready (no CI wait).  

## Continuation Prompt

```
Read task.md. Resolve #98 and #99 conflicts vs main (post-#96).
Prefer rebase; keep capture auth + asset profile chrome.
Do not poll CI. Keep e2e/ untracked. AGENTS ≤3 tools/turn.
```
