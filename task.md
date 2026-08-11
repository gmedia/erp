# task.md — Active Session Handoff

**Last updated:** 2026-08-11  
**Current milestone:** Visual audit — My Approvals chrome (**PR #96**) rebased onto main (post-#97)  
**Branch:** `feat/va-my-approvals-chrome`  
**main tip:** includes **#97** harness presets  
**Open PRs:** [#96](https://github.com/gmedia/erp/pull/96) My Approvals · [#98](https://github.com/gmedia/erp/pull/98) capture auth (MERGEABLE after rebase)  
**Vision session:** multimodal-looker `ses_02021a5c2ffeaD0HIIg6ymhnEW`

## Read order

1. `task.md` (this)  
2. `docs/visual-audit/BACKLOG.md`  
3. `docs/visual-audit/FINDINGS.md`  
4. `docs/visual-audit-plan.md`

## Done

- Wave 0–1 harness + plan + FINDINGS/BACKLOG (PR #86)  
- **HF-1–3** on main  
- **T1–T5** PRs #90–#94 merged  
- **Closeout** #95 merged  
- Full re-smoke **84/85** (`/asset-models` → login; fixed on **#98**)  
- **Harness allowlist** — **#97 merged** (`VISUAL_AUDIT_PRESET` + `presets.json`)  
- **My Approvals exception chrome** — this branch / **PR #96**  
  - Readable type labels; document-first titles; denser cards; `PageHeader` + counts; tab badges; absolute+relative times  
  - `npm run types` clean  

## Themes status

| ID | Theme | Status |
|----|-------|--------|
| T1–T5 | Shared shell | **merged** |
| Closeout | BACKLOG / task | **merged** (#95) |
| Harness | Named presets | **merged** (#97) |
| EX-1 | My Approvals inbox | **PR #96** (this branch) |
| EX-auth | Capture auth settle | **PR #98** |

## Do not

- Mass-capture Wave 2 / remaining ~78 routes  
- Use default `playwright.config.ts` for visual (`migrate:fresh`)  
- Commit untracked `e2e/`  
- Wait on CI  

## Recommended next

1. Force-push this branch after rebase → clear #96 conflicts.  
2. Merge #96 / #98 when green (do not poll CI).  
3. Next product exception: **asset profile** or dense modal (new branch from main).

## Continuation Prompt

Finish #96 rebase/push. #98 already rebased MERGEABLE. Keep one task per branch. Do not poll CI. Keep `e2e/` untracked.
