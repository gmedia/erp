# task.md — Active Session Handoff

**Last updated:** 2026-08-11  
**Current milestone:** Visual audit — #98 merged; finish #99 rebase  
**Branch tip (this work):** `feat/va-asset-profile-chrome` (rebasing onto main)  
**main tip:** **#96** My Approvals + **#97** harness + **#98** capture auth  
**Open PRs:** [#99](https://github.com/gmedia/erp/pull/99) asset profile  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 · **HF-1–3** · **T1–T5** · **Closeout #95** · **Harness #97** · **My Approvals #96** · **Capture auth #98** — **on main**  
- **Asset profile chrome** — this branch / **PR #99**  
  - Gradient hero → `PageHeader`; compact tabs/cards; QR + EntityState* kept  
  - `npm run types` clean  

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** |
| Harness | Named presets | **merged** (#97) |
| EX-1 | My Approvals inbox | **merged** (#96) |
| EX-auth | Capture auth settle | **merged** (#98) |
| EX-asset-profile | Asset profile densify | **PR #99** (rebasing) |

## Do not

- Mass Wave 2 · default playwright visual · commit `e2e/` · wait on CI · fan-out >3 tools/turn  

## Recommended next

1. Finish #99 rebase → force-with-lease → MERGEABLE.  
2. Merge #99 when ready (no CI wait).  
3. No mass Wave 2.

## Continuation Prompt

```
Read task.md. #98 merged. Finish #99 rebase/push if needed.
Do not poll CI. Keep e2e/ untracked. AGENTS ≤3 tools/turn.
```
