# task.md — Active Session Handoff

**Last updated:** 2026-08-11  
**Current milestone:** Visual audit — #98 merged; #99 rebased onto main  
**Branch tip (this work):** `feat/va-asset-profile-chrome`  
**main tip:** **#96** + **#97** + **#98**  
**Open PRs:** [#99](https://github.com/gmedia/erp/pull/99) asset profile  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- T1–T5 · Closeout #95 · Harness #97 · My Approvals **#96** · Capture auth **#98** — **on main**  
- **Asset profile chrome** — this branch / **PR #99**  
  - Gradient hero → `PageHeader`; compact tabs/cards; QR + EntityState* kept  
  - `npm run types` clean  

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 / Harness / EX-1 / EX-auth | shell + presets + My Approvals + capture | **merged** |
| EX-asset-profile | Asset profile densify | **PR #99** (rebasing) |

## Do not

- Mass Wave 2 · commit `e2e/` · wait/poll CI · stack more work on this PR  

## Recommended next

1. Finish rebase → force-with-lease → MERGEABLE.  
2. User merges **#99** when ready.  
3. No mass Wave 2.

## Continuation Prompt

```
Read task.md. #98 merged. Finish #99 rebase/push if needed.
Do not poll CI. Keep e2e/ untracked.
```
